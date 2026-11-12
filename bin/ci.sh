#!/usr/bin/env bash

# Replays the CI locally, but on fresh dependencies: the development machine's vendor stays at the versions of its composer.lock, which can lag behind Packagist. This script works on a clean copy, where `composer update` only sees the published versions - exactly what GitHub sees. Every c975L bundle carries this file byte for byte: change it in all of them at once.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_VERSIONS="8.4 8.5 8.6"
PHP_LOCAL="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
COMPOSER="$(command -v composer)"

WORK="$(mktemp -d)"
PIDS=()

# Stops the background test runs before removing their copies, so that a failure of the main run leaves nothing writing into a deleted directory
Cleanup() {
    for Pid in "${PIDS[@]}"; do
        pkill -TERM -P "$Pid" 2> /dev/null || true
        kill "$Pid" 2> /dev/null || true
    done
    wait 2> /dev/null || true
    rm -rf "$WORK"
}
trap Cleanup EXIT

# Copies the repository without vendor/ and composer.lock, to force a fresh resolution from Packagist; uncommitted changes are kept, since they are exactly what has to be validated before pushing
CopyRepository() {
    rsync -a \
        --exclude '.git/' \
        --exclude 'vendor/' \
        --exclude 'composer.lock' \
        --exclude '.phpunit.cache/' \
        --exclude '.php-cs-fixer.cache' \
        --exclude '.phpunit.result.cache' \
        --exclude '.rector.cache/' \
        --exclude 'coverage/' \
        "$ROOT/" "$1/"
}

# Runs the tests of one other PHP version in its own copy, resolving its own dependencies since a lock made on one version may not install on another; phpunit is called directly, its shebang would otherwise pick the machine's default PHP; a lower priority leaves the CPU to `composer qa`, whose Rector workers fail under load with a false syntax error
TestVersion() {
    local Version=$1 Directory="$WORK/php$1"
    renice -n 10 "$BASHPID" > /dev/null
    export TMPDIR="$Directory.tmp"
    mkdir -p "$TMPDIR"
    cd "$Directory"
    "php$Version" "$COMPOSER" update --no-interaction --no-progress
    "php$Version" vendor/bin/phpunit
}

# Gives the binary `composer qa` runs: Composer puts vendor/bin ahead of the PATH in its scripts, so a tool require-dev carries wins over the fresh one
Tool() {
    if [ -x "vendor/bin/$1" ]; then echo "vendor/bin/$1"; else echo "$1"; fi
}

# Prints the version a tool states
ToolVersion() {
    "$(Tool "$1")" --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1
}

# rector.php caches in .rector.cache, inside the repository: that cache is left out of the copy, so this replay starts from the cold cache the CI always has; a private TMPDIR per run isolates whatever else writes there, and goes away with $WORK
echo "→ Copie du dépôt vers $WORK"
CopyRepository "$WORK/repo"

# The other PHP versions the CI tests start at once in the background, each in a copy and a log of its own, while the default PHP's run stays on screen
VERSIONS=()
for Version in $PHP_VERSIONS; do
    [ "$Version" = "$PHP_LOCAL" ] && continue

    if ! command -v "php$Version" > /dev/null; then
        echo "⚠  PHP $Version absent de cette machine : ses tests ne tournent qu'en CI."
        continue
    fi

    echo "→ Tests sur PHP $Version lancés en parallèle"
    CopyRepository "$WORK/php$Version"
    (TestVersion "$Version") > "$WORK/php$Version.log" 2>&1 &
    PIDS+=("$!")
    VERSIONS+=("$Version")
done

export TMPDIR="$WORK/tmp"
mkdir -p "$TMPDIR"
cd "$WORK/repo"

echo "→ composer update (Packagist, sans le vendor ni le composer.lock locaux)"
composer update --no-interaction --no-progress

# The quality tools are not dependencies of the bundle, PHPStan aside: the CI installs them with setup-php, which always takes the latest release, while the development machine keeps whatever was installed the day it was installed, so replaying `composer qa` with the machine's own tools proves nothing. They are installed fresh, through Composer rather than as setup-php's phars (same rules, different packaging), one isolated project per tool so their dependencies never have to agree, and put ahead of everything else in the PATH
echo "→ Outils qualité, en dernière version comme la CI"
TOOLS="$WORK/tools"
for Package in squizlabs/php_codesniffer friendsofphp/php-cs-fixer rector/rector; do
    Directory="$TOOLS/$(basename "$Package")"
    mkdir -p "$Directory"
    composer --working-dir="$Directory" require "$Package" --no-interaction --no-progress --quiet
    PATH="$Directory/vendor/bin:$PATH"
done

# Lizard is a Python tool taking its latest release like the PHP ones above, as the CI and the local one do: pinning one side alone lets the counts drift apart
python3 -m venv "$TOOLS/lizard"
"$TOOLS/lizard/bin/pip" install --quiet lizard
PATH="$TOOLS/lizard/bin:$PATH"
export PATH

# Stated rather than assumed: this is the very line that was missing when a tool's release broke the CI on an unchanged repository
printf '   phpcs %s | phpstan %s | php-cs-fixer %s | rector %s | lizard %s\n' \
    "$(ToolVersion phpcs)" "$(ToolVersion phpstan)" "$(ToolVersion php-cs-fixer)" "$(ToolVersion rector)" "$(ToolVersion lizard)"

echo "→ Contrôles qualité sur PHP $PHP_LOCAL"
composer qa

# Waits for each background run, its whole log printed only when it failed
FAILED=0
for Index in "${!VERSIONS[@]}"; do
    Version=${VERSIONS[$Index]}
    if wait "${PIDS[$Index]}"; then
        echo "✓ Tests sur PHP $Version"
    else
        echo "✗ Tests sur PHP $Version, journal :"
        cat "$WORK/php$Version.log"
        FAILED=1
    fi
done
PIDS=()

if [ "$FAILED" -ne 0 ]; then
    exit 1
fi

echo
echo "✓ Les contrôles de la CI passent sur des dépendances fraîches."
