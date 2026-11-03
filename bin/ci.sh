#!/usr/bin/env bash

# Replays the CI locally, but on fresh dependencies: the development machine's vendor stays at the versions of its composer.lock, which can lag behind Packagist. This script works on a clean copy, where `composer update` only sees the published versions - exactly what GitHub sees.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_VERSIONS="8.4 8.5 8.6"
PHP_LOCAL="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

# rector.php caches in .rector.cache, inside the repository rather than in the sys_get_temp_dir() directory shared by every repository on the machine. That cache is left out of the copy below, so this replay starts from the cold cache the CI always has: without it a second run answers "Rector is done!" where the first listed files to rewrite. A private TMPDIR still isolates whatever else writes there, and goes away with $WORK
export TMPDIR="$WORK/tmp"
mkdir -p "$TMPDIR"

# vendor/ and composer.lock are excluded to force a fresh resolution from Packagist; uncommitted changes are kept, since they are exactly what has to be validated before pushing
echo "→ Copie du dépôt vers $WORK"
rsync -a \
    --exclude '.git/' \
    --exclude 'vendor/' \
    --exclude 'composer.lock' \
    --exclude '.phpunit.cache/' \
    --exclude '.php-cs-fixer.cache' \
    --exclude '.phpunit.result.cache' \
    --exclude '.rector.cache/' \
    --exclude 'coverage/' \
    "$ROOT/" "$WORK/"

cd "$WORK"

echo "→ composer update (Packagist, sans le vendor ni le composer.lock locaux)"
composer update --no-interaction --no-progress

# The quality tools are not dependencies of the bundle - PHPStan aside, which require-dev carries for its deprecation rules and composer update has just installed: the CI installs them with setup-php, which always takes the latest release, while the development machine keeps whatever was installed the day it was installed. Replaying `composer qa` with the machine's own tools therefore proves nothing - a rule removed upstream since is still enforced here, and a rule added since is missed. They are installed fresh, one isolated project per tool so their own dependencies never have to agree with each other, and put ahead of everything else in the PATH. setup-php installs phars where this installs the same versions through Composer: same rules, different packaging
echo "→ Outils qualité, en dernière version comme la CI"
TOOLS="$WORK/.ci-tools"
for Package in squizlabs/php_codesniffer friendsofphp/php-cs-fixer rector/rector; do
    Directory="$TOOLS/$(basename "$Package")"
    mkdir -p "$Directory"
    composer --working-dir="$Directory" require "$Package" --no-interaction --no-progress --quiet
    PATH="$Directory/vendor/bin:$PATH"
done

# Lizard is a Python tool taking its latest release like the four above, as the CI and the local one do: pinning one side alone lets the counts drift apart
python3 -m venv "$TOOLS/lizard"
"$TOOLS/lizard/bin/pip" install --quiet lizard
PATH="$TOOLS/lizard/bin:$PATH"
export PATH

# Stated rather than assumed: this is the very line that was missing when a tool's release broke the CI on an unchanged repository
printf '   phpcs %s | phpstan %s | php-cs-fixer %s | rector %s | lizard %s\n' \
    "$(phpcs --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)" \
    "$(vendor/bin/phpstan --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)" \
    "$(php-cs-fixer --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)" \
    "$(rector --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)" \
    "$(lizard --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"

echo "→ Contrôles qualité"
composer qa

# The other PHP versions the CI tests: each resolves its own dependencies, since a lock made on one version may not install on another. phpunit is called directly, its shebang would otherwise pick the machine's default PHP
COMPOSER="$(command -v composer)"
for Version in $PHP_VERSIONS; do
    [ "$Version" = "$PHP_LOCAL" ] && continue

    if ! command -v "php$Version" > /dev/null; then
        echo "⚠  PHP $Version absent de cette machine : ses tests ne tournent qu'en CI."
        continue
    fi

    echo "→ Tests sur PHP $Version, dépendances résolues pour cette version"
    rm -rf vendor composer.lock
    "php$Version" "$COMPOSER" update --no-interaction --no-progress --quiet
    "php$Version" vendor/bin/phpunit
done

echo
echo "✓ Les contrôles de la CI passent sur des dépendances fraîches."
