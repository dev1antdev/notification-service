<?php

declare(strict_types=1);

namespace App\UI\Cli\Command;

use App\Infrastructure\Outbox\Messenger\OutboxDispatcher;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'outbox:dispatch')]
final class OutboxDispatchCommand extends Command
{
    public function __construct(private readonly OutboxDispatcher $dispatcher)
    {
        parent::__construct();
    }

    /**
     * @throws \Throwable
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption('limit'));
        $loop = (bool)$input->getOption('loop');
        $sleepMs = max(10, (int)$input->getOption('sleep-ms'));

        do {
            $count = $this->dispatcher->dispatchBatch($limit);

            if ($count > 0) {
                $output->writeln("Dispatched {$count} outbox events.");
            }

            if ($loop) {
                usleep($sleepMs * 1000);
            }
        } while ($loop);

        return Command::SUCCESS;
    }
}
