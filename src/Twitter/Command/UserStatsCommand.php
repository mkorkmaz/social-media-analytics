<?php
declare(strict_types = 1);

namespace Twitter\Command;

use Twitter\Model;
use ServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserStatsCommand extends Command
{
    private $model;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('twitter:user_stats')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
            ->addOption(
                'return',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Returns data instead of printing.',
                0
            )
            // the short description shown while running "php bin/console list"
            ->setDescription('Gets user\'s stats.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get twitter user\'s statistics including follower count, following count and share count...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = ServiceProvider::getInstance();
        $this->model = Model::factory($provider);
        $username = $input->getArgument('username');
        $return = $input->getOption('return');
        if ($return === 0) {
            $output->writeln('Username: ' . $username);
        }
        $userStats = $this->model->getUserData($username);
        if ($return === 0) {
            var_dump($userStats);
        } else {
            $output->writeln(json_encode($userStats));
        }
    }
}