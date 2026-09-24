<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Controller\Management;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use c975L\PurchaseCreditsBundle\Entity\CreditPack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

use function Symfony\Component\Translation\t;

// The packs on sale - a number of credits and its price
class CreditPackCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigServiceInterface $configService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CreditPack::class;
    }

    // Prices are the admin's, as every price of the shop
    private function roleNeeded(): string
    {
        return (string) $this->configService->get('site-role-admin');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(t('label.pack', [], 'purchasecredits'))
            ->setEntityLabelInPlural(t('label.packs', [], 'purchasecredits'))
            ->setEntityPermission($this->roleNeeded())
            ->setDefaultSort(['position' => 'ASC', 'credits' => 'ASC'])
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', '@c975LPurchaseCredits/management/credit_pack_crud_index.html.twig')
        ;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        foreach ([Action::INDEX, Action::NEW, Action::EDIT, Action::DELETE] as $action) {
            $actions->setPermission($action, $this->roleNeeded());
        }

        return $actions;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('credits', t('label.pack_credits', [], 'purchasecredits'));

        yield MoneyField::new('price', t('label.pack_price', [], 'purchasecredits'))
            ->setCurrency((string) ($this->configService->get('shop-currency') ?: 'EUR'))
            ->setStoredAsCents()
        ;

        yield NumberField::new('vat', t('label.pack_vat', [], 'purchasecredits'))
            ->setNumDecimals(1)
            ->hideOnIndex()
        ;

        yield IntegerField::new('position', t('label.pack_position', [], 'purchasecredits'))->hideOnIndex();

        yield BooleanField::new('published', t('label.pack_published', [], 'purchasecredits'));
    }
}
