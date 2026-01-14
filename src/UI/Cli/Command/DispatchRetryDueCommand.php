<?php

declare(strict_types=1);

namespace App\UI\Cli\Command;

use App\Application\Delivery\Dispatch\DispatchDeliveryCommand;
use App\Domain\Delivery\Repository\DeliveryFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'deliveries:dispatch-retries')]
final class DispatchRetryDueCommand extends Command
{
    public function __construct(
        private readonly DeliveryFinder $deliveryFinder,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Batch size', '200');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('limit');
        $now = new \DateTimeImmutable();

        $ids = $this->deliveryFinder->findRetryDue($now, $limit);

        foreach ($ids as $id) {
            $this->messageBus->dispatch(new DispatchDeliveryCommand($id));
        }

        $output->writeln("Dispatched retry-due deliveries: " . count($ids));

        return Command::SUCCESS;
    }
}
