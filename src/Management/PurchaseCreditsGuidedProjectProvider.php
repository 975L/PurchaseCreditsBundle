<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Management;

use c975L\ConfigBundle\Management\GuidedProjectProviderInterface;
use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditPackCrudController;
use c975L\PurchaseCreditsBundle\Controller\Management\CreditTransactionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// This bundle's guided projects, running the 10000 block GuidedProjectProviderInterface reserves them. Only the opening step of each carries an url: from there the parcours walks the screen the user has been sent to, highlighting the button or the field they are meant to use next
class PurchaseCreditsGuidedProjectProvider implements GuidedProjectProviderInterface
{
    // SiteBundle's pages screen, named as a string: this bundle does not require SiteBundle, the one holding the pages a block is placed on
    private const string SITE_BUNDLE = 'c975LSiteBundle';
    private const string PAGE_CRUD_CONTROLLER = 'c975L\SiteBundle\Controller\Management\PageCrudController';

    /** @param array<string, class-string> $bundles */
    public function __construct(
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly ConfigServiceInterface $configService,
        #[Autowire(param: 'kernel.bundles')]
        private readonly array $bundles = [],
    ) {
    }

    public function getGuidedProjects(): array
    {
        $projects = [
            $this->packProject(),
            $this->giftProject(),
        ];

        // Read on the registered bundles rather than with class_exists(): what matters is what the app booted, not what happens to sit in vendor/
        if (array_key_exists(self::SITE_BUNDLE, $this->bundles)) {
            $projects[] = $this->blockProject();
        }

        return $projects;
    }

    // Putting a pack on sale, the one thing to do before a visitor can buy any credit
    private function packProject(): array
    {
        return [
            'slug' => 'purchasecredits-pack',
            'label' => 'label.guided_project_purchasecredits_pack',
            'description' => 'description.guided_project_purchasecredits_pack',
            'translation_domain' => 'purchasecredits',
            'order' => 10010,
            'role' => $this->roleNeeded(),
            'steps' => [
                [
                    'label' => 'label.guided_step_purchasecredits_pack_open',
                    'description' => 'description.guided_step_purchasecredits_pack_open',
                    'narration' => 'narration.guided_step_purchasecredits_pack_open',
                    'url' => $this->indexUrl(CreditPackCrudController::class),
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_pack_new',
                    'description' => 'description.guided_step_purchasecredits_pack_new',
                    'narration' => 'narration.guided_step_purchasecredits_pack_new',
                    'highlight' => '.action-new',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_pack_credits',
                    'description' => 'description.guided_step_purchasecredits_pack_credits',
                    'narration' => 'narration.guided_step_purchasecredits_pack_credits',
                    'highlight' => '#CreditPack_credits',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_pack_price',
                    'description' => 'description.guided_step_purchasecredits_pack_price',
                    'narration' => 'narration.guided_step_purchasecredits_pack_price',
                    'highlight' => '#CreditPack_price',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_pack_published',
                    'description' => 'description.guided_step_purchasecredits_pack_published',
                    'narration' => 'narration.guided_step_purchasecredits_pack_published',
                    'highlight' => '#CreditPack_published',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_pack_done',
                    'description' => 'description.guided_step_purchasecredits_pack_done',
                    'narration' => 'narration.guided_step_purchasecredits_pack_done',
                ],
            ],
        ];
    }

    // Giving or taking credits by hand, a line of the ledger that is never edited afterwards - a mistake is corrected by the line reversing it
    private function giftProject(): array
    {
        return [
            'slug' => 'purchasecredits-gift',
            'label' => 'label.guided_project_purchasecredits_gift',
            'description' => 'description.guided_project_purchasecredits_gift',
            'translation_domain' => 'purchasecredits',
            'order' => 10020,
            'role' => $this->roleNeeded(),
            'steps' => [
                [
                    'label' => 'label.guided_step_purchasecredits_gift_open',
                    'description' => 'description.guided_step_purchasecredits_gift_open',
                    'narration' => 'narration.guided_step_purchasecredits_gift_open',
                    'url' => $this->indexUrl(CreditTransactionCrudController::class),
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_gift_new',
                    'description' => 'description.guided_step_purchasecredits_gift_new',
                    'narration' => 'narration.guided_step_purchasecredits_gift_new',
                    'highlight' => '.action-new',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_gift_user',
                    'description' => 'description.guided_step_purchasecredits_gift_user',
                    'narration' => 'narration.guided_step_purchasecredits_gift_user',
                    'highlight' => '#CreditTransaction_user',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_gift_amount',
                    'description' => 'description.guided_step_purchasecredits_gift_amount',
                    'narration' => 'narration.guided_step_purchasecredits_gift_amount',
                    'highlight' => '#CreditTransaction_amount',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_gift_description',
                    'description' => 'description.guided_step_purchasecredits_gift_description',
                    'narration' => 'narration.guided_step_purchasecredits_gift_description',
                    'highlight' => '#CreditTransaction_description',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_gift_done',
                    'description' => 'description.guided_step_purchasecredits_gift_done',
                    'narration' => 'narration.guided_step_purchasecredits_gift_done',
                ],
            ],
        ];
    }

    // Placing the packs block on a page, the step that shows the packs to visitors. The mechanics of a block are SiteBundle's own "site-block" parcours, this one only names the kind to pick
    private function blockProject(): array
    {
        return [
            'slug' => 'purchasecredits-block',
            'label' => 'label.guided_project_purchasecredits_block',
            'description' => 'description.guided_project_purchasecredits_block',
            'translation_domain' => 'purchasecredits',
            'order' => 10030,
            'role' => $this->configService->get('site-role-editor'),
            'steps' => [
                [
                    'label' => 'label.guided_step_purchasecredits_block_open',
                    'description' => 'description.guided_step_purchasecredits_block_open',
                    'narration' => 'narration.guided_step_purchasecredits_block_open',
                    'url' => $this->indexUrl(self::PAGE_CRUD_CONTROLLER),
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_block_edit',
                    'description' => 'description.guided_step_purchasecredits_block_edit',
                    'narration' => 'narration.guided_step_purchasecredits_block_edit',
                    'highlight' => '.action-edit',
                ],
                [
                    // The add button of UiBundle's block collection, the same selector SiteBundle's own parcours points at
                    'label' => 'label.guided_step_purchasecredits_block_add',
                    'description' => 'description.guided_step_purchasecredits_block_add',
                    'narration' => 'narration.guided_step_purchasecredits_block_add',
                    'highlight' => '[data-ui-sort-group="block"]:not([data-ui-move-target])',
                ],
                [
                    // The row marker UiBundle's BlockType puts on the kind picker
                    'label' => 'label.guided_step_purchasecredits_block_kind',
                    'description' => 'description.guided_step_purchasecredits_block_kind',
                    'narration' => 'narration.guided_step_purchasecredits_block_kind',
                    'highlight' => '[data-kind-row]',
                ],
                [
                    'label' => 'label.guided_step_purchasecredits_block_save',
                    'description' => 'description.guided_step_purchasecredits_block_save',
                    'narration' => 'narration.guided_step_purchasecredits_block_save',
                    'highlight' => '.action-saveAndReturn',
                ],
            ],
        ];
    }

    // The listing a parcours opens on
    private function indexUrl(string $controller): string
    {
        return $this->adminUrlGenerator
            ->unsetAll()
            ->setController($controller)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;
    }

    // The role both CRUD controllers sit behind - a parcours walking screens the user can't open reads as a broken one
    private function roleNeeded(): string
    {
        return (string) $this->configService->get('site-role-admin');
    }
}
