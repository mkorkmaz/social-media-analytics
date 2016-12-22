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

class TweetStatsCommand extends Command
{
    private $model;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('twitter:tweet_stats')
            ->addOption(
                'return',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Returns data instead of printing.',
                0
            )
            // the short description shown while running "php bin/console list"
            ->setDescription('Updates tweets\' stats.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get all the tweets\' statistics including follower count, following count and share count...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = ServiceProvider::getInstance();
        $this->model = Model::factory($provider);
        $return = $input->getOption('return');
        $output->writeln(date("Y-m-d H:i:s") . ' Updating all the  tweets\' data');
        $this->model->updateTweetData();
    }
}