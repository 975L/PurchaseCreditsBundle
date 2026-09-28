<?php

/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\Command;

use c975L\ConfigBundle\Contract\UserInterface;
use c975L\PurchaseCreditsBundle\Service\CreditServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// Gives credits to an account from the console, as a line of the ledger like any other: a goodwill gesture, or the credits a tutorial account needs before it is filmed spending them
#[AsCommand(
    name: 'c975l:purchasecredits:grant',
    description: 'Gives credits to an account, written to its ledger'
)]
class GrantCreditsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreditServiceInterface $creditService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the account')
            ->addArgument('credits', InputArgument::REQUIRED, 'Number of credits given')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'What the ledger line says', 'Credits granted')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $credits = filter_var($input->getArgument('credits'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $credits) {
            $output->writeln('<error>The credits must be a whole number above zero.</error>');

            return Command::FAILURE;
        }

        // The application's user entity, reached through the interface ConfigBundle maps onto it
        $email = (string) $input->getArgument('email');
        $user = $this->entityManager->getRepository(UserInterface::class)->findOneBy(['email' => $email]);
        if (!$user instanceof UserInterface) {
            $output->writeln(sprintf('<error>No account for "%s".</error>', $email));

            return Command::FAILURE;
        }

        $this->creditService->grant($user, $credits, (string) $input->getOption('description'));
        $output->writeln(sprintf('%d credit(s) granted to %s, balance %d.', $credits, $email, $this->creditService->getBalance($user)));

        return Command::SUCCESS;
    }
}
