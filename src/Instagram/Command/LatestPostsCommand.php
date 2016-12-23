<?php
declare(strict_types = 1);

namespace Instagram\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog;
use Instagram;
use ServiceProvider;

class LatestPostsCommand extends Command
{
    private $logger;

    private $instagramModel;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/sma")
            ->setName('instagram:latest_posts')
            ->addArgument('username', InputArgument::REQUIRED, 'The instagram username of the user.')
            ->addOption(
                'return',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Returns data instead of printing.',
                0
            )
            // the short description shown while running "php bin/sma list"
            ->setDescription('Gets user\'s latest posts.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get twitter user\'s latest posts.');
    }

    private function setServices(
        Monolog\Logger $logger,

        Instagram\Model $instagramModel
    ) {
        $this->logger = $logger;
        $this->instagramModel = $instagramModel;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Instagram\Model::factory($provider)
        );
        $username = $input->getArgument('username');
        $return = $input->getOption('return');
        if ($return === 0) {
            $output->writeln(date('Y-m-d H:i:s') . ' User ID: ' . $username);
        }
        if (empty($username)) {
            $output->writeln(date('Y-m-d H:i:s') . ' <error>Username is empty:</error>');

        }
        $medias = $this->instagramModel->getLatestPosts($username);
        if ($return === 0) {
            var_dump($medias);
        } else {
            $output->writeln(json_encode($medias));
        }
    }
}