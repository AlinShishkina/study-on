<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-sequences',
    description: 'Add a short description for your command',
)]
class ResetSequencesCommand extends Command
{
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sequences = ['course_id_seq', 'lesson_id_seq'];

        foreach ($sequences as $sequence) {
            $sql = sprintf("SELECT setval('%s', 1, false);", $sequence);
            $this->connection->executeQuery($sql);
            $output->writeln(sprintf('Sequence "%s" reset', $sequence));
        }

        $output->writeln('Sequences reset complete');

        return Command::SUCCESS;
    }
}