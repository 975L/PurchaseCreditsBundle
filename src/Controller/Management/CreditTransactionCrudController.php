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
use c975L\PurchaseCreditsBundle\Entity\CreditTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

use function Symfony\Component\Translation\t;

// The ledger: read, and written by hand only to add a line - a gift, a correction. A line is never edited nor deleted, a mistake being corrected by the line that reverses it
class CreditTransactionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConfigServiceInterface $configService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CreditTransaction::class;
    }

    private function roleNeeded(): string
    {
        return (string) $this->configService->get('site-role-admin');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(t('label.transaction', [], 'purchasecredits'))
            ->setEntityLabelInPlural(t('label.transactions', [], 'purchasecredits'))
            ->setEntityPermission($this->roleNeeded())
            ->setDefaultSort(['createdAt' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields(['description', 'reference'])
            ->overrideTemplate('crud/index', '@c975LPurchaseCredits/management/credit_transaction_crud_index.html.twig')
        ;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::INDEX, $this->roleNeeded())
            ->setPermission(Action::NEW, $this->roleNeeded())
            ->setPermission(Action::DETAIL, $this->roleNeeded())
        ;
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('user', t('label.transaction_user', [], 'purchasecredits')));
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('createdAt', t('label.transaction_date', [], 'purchasecredits'))->hideOnForm();

        yield AssociationField::new('user', t('label.transaction_user', [], 'purchasecredits'));

        yield IntegerField::new('amount', t('label.transaction_amount', [], 'purchasecredits'))
            ->setHelp(t('help.transaction_amount', [], 'purchasecredits'))
        ;

        yield TextField::new('description', t('label.transaction_description', [], 'purchasecredits'));

        yield TextField::new('reference', t('label.transaction_reference', [], 'purchasecredits'))->hideOnForm();
    }
}
