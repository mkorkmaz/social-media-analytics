<?php
declare(strict_types = 1);

namespace Twitter\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog;
use Twitter;
use ServiceProvider;

class LatestPostsCommand extends Command
{
    private $logger;

    private $twitterModel;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/sma")
            ->setName('twitter:latest_posts')
            ->addArgument('user_id', InputArgument::REQUIRED, 'The twitter id of the user.')
            ->addOption(
                'return',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Returns data instead of printing.',
                0
            )
            // the short description shown while running "php bin/sma list"
            ->setDescription('Gets user\'s stats.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get twitter user\'s statistics including follower count, '
                . 'following count and share count...');
    }

    private function setServices(
        Monolog\Logger $logger,

        Twitter\Model $twitterModel
    ) {
        $this->logger = $logger;
        $this->twitterModel = $twitterModel;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Twitter\Model::factory($provider)
        );
        $user_id = $input->getArgument('user_id');
        $return = $input->getOption('return');
        if ($return === 0) {
            $output->writeln('User ID: ' . $user_id);
        }
        $posts = $this->twitterModel->getLatestPost($user_id);
        if ($return === 0) {
            var_dump($posts);
        } else {
            $output->writeln(json_encode($posts));
        }
    }
}