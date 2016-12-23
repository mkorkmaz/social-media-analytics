<?php

/**
 * PHP version 7
 *
 * @category Command
 * @package  Users
 * @author   Mehmet Korkmaz <mehmet@mkorkmaz.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/mkorkmaz/social-media-stats
 */

declare(strict_types = 1);

namespace Users\Command;

use Users\Model;
use ServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Monolog;
use Twitter;
use Instagram;

/**
 * Class OperationsCommand
 *
 * @package Users\Command
 */

class OperationsCommand extends Command
{
    private $logger;
    private $userModel;
    private $twitterModel;
    private $instagramModel;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/sma")
            ->setName('user:op')
            ->addArgument('operation', InputArgument::REQUIRED, 'Operation to be done')

            ->addOption(
                'username',
                'un',
                InputOption::VALUE_OPTIONAL,
                'User\'s name.'
            )
            ->addOption(
                'twitter',
                't',
                InputOption::VALUE_OPTIONAL,
                'User\'s twitter user name or id.'
            )
            ->addOption(
                'instagram',
                'i',
                InputOption::VALUE_OPTIONAL,
                'User\'s Instagram user name or id.'
            )
            // the short description shown while running "php bin/sma list"
            ->setDescription('Make user operations.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to make user operations such as add, update, delete etc.');
    }

    private function setServices(
        Monolog\Logger $logger,
        Model $userModel,
        Twitter\Model $twitterModel,
        Instagram\Model $instagramModel
    ) {
        $this->logger = $logger;
        $this->userModel = $userModel;
        $this->twitterModel = $twitterModel;
        $this->instagramModel = $instagramModel;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Model::factory($provider),
            Twitter\Model::factory($provider),
            Instagram\Model::factory($provider)
        );


        $operation = $input->getArgument('operation');
        $this->logger->info('user:op => ' . $operation);
        switch ($operation) {
        case 'add':
            $this->add($input, $output);
            break;
        case 'update_all':
                $this->updateAll($input, $output);
                break;
        case 'latest_all':
                $this->getLatestsAll($input, $output);
                break;
        case 'latest_posts':
            $this->latestPosts($input, $output);
            break;
        default:
            $this->logger->error("user:op for {$operation} failed");
            $output->writeln(date('Y-m-d H:i:s')
                . ' <error>\n\n  Operation <bg=red;options=bold,underscore>"'
                . $operation . '"</> not found for user:op!\n</error>');
        }
    }

    private function add(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getOption('username');
        $twitterUserName = $input->getOption('twitter');
        $instagramUserName = $input->getOption('instagram');
        $user_id = $this->userModel->add($username, $twitterUserName, $instagramUserName);

        if (!empty($user_id)) {
            $output->writeln(date('Y-m-d H:i:s') .' <info>' . $username .' added.<info>');
            $output->writeln(date('Y-m-d H:i:s') . ' User ID: ' . $user_id);
        }
        if (!empty($twitterUserName)) {
            $output->writeln(date('Y-m-d H:i:s').' Updating Twitter stats');
            $this->updateTwitterStats($user_id, $twitterUserName);

            $output->writeln(date('Y-m-d H:i:s') . ' Twitter stats updated');
        }
        if (!empty($instagramUserName)) {
            $output->writeln(date('Y-m-d H:i:s') . ' Instagram Twitter stats');
            $this->updateInstagramStats($user_id, $instagramUserName);
            $output->writeln(date('Y-m-d H:i:s') . ' Instagram stats updated');

        }
        $output->writeln(date('Y-m-d H:i:s') . ' Command executed');
    }

    private function updateTwitterStats(string $user_id, string $twitterUserName)
    {
        $data = $this->runTwitterUserStatsCommand($twitterUserName);
        $this->userModel->updateTwitterStats($user_id, $data);
    }

    private function runTwitterUserStatsCommand(string $twitterUserName)
    {
        $command = $this->getApplication()->find('twitter:user_stats');
        $arguments = array(
            'command'   => 'twitter:user_stats',
            'username'  => $twitterUserName,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }

    private function updateInstagramStats(string $user_id, string $instagramUserName)
    {
        $data = $this->runInstagramUserStatsCommand($instagramUserName);
        $this->userModel->updateInstagramStats($user_id, $data);
    }

    private function runInstagramUserStatsCommand(string $instagramUserName)
    {
        $command = $this->getApplication()->find('instagram:user_stats');
        $arguments = array(
            'command'   => 'instagram:user_stats',
            'username'  => $instagramUserName,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }


    private function latestPosts(InputInterface $input, OutputInterface $output)
    {
        $twitterUserID = $input->getOption('twitter');
        $instagramUserID = $input->getOption('instagram');

        if (empty($twitterUserID|$instagramUserID)) {
            $output->writeln(date('Y-m-d H:i:s') . ' <warning>'
                . ($twitterUserID|$instagramUserID) .' not found.<warning>');
        }
        if (!empty($twitterUserID)) {
            $output->writeln(date('Y-m-d H:i:s') . ' Updating Twitter stats');
            $output->writeln(date('Y-m-d H:i:s') . ' Twitter latest_posts are fetched.');

            $this->getLatestTwitterPosts($twitterUserID);
        }
        if (!empty($instagramUserID)) {
            $output->writeln(date('Y-m-d H:i:s') . ' Instagram Twitter stats');
            $this->getLatestInstagramPosts($instagramUserID);
            $output->writeln(date('Y-m-d H:i:s') . ' Instagram latest_posts are fetched.');

        }
        sleep(1);
        $output->writeln(date('Y-m-d H:i:s') . 'Command "user:latest_posts" executed.');
    }

    private function getLatestTwitterPosts(string $user_id)
    {
        $tweets = $this->runTwitterLatestPostsCommand($user_id);
        $this->twitterModel->processLatestTweets($user_id, $tweets);
    }

    private function runTwitterLatestPostsCommand(string $user_id)
    {
        $command = $this->getApplication()->find('twitter:latest_posts');
        $arguments = array(
            'command'   => 'twitter:latest_posts',
            'user_id'   => $user_id,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $returnCode = $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }

    private function getLatestInstagramPosts(string $user_id)
    {
        $username = $this->instagramModel->getUsernameById($user_id);
        $medias = $this->runInstagramLatestPostsCommand($username);
        $this->instagramModel->processLatestMedias($user_id, $medias);
    }

    private function runInstagramLatestPostsCommand(string $username)
    {
        $command = $this->getApplication()->find('instagram:latest_posts');
        $arguments = array(
            'command'   => 'instagram:latest_posts',
            'username'   => $username,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $returnCode = $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }

    private function updateAll(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userModel->getActiveUsers();
        foreach ($users['data'] as $user) {
            $output->writeln(date('Y-m-d H:i:s') . " {$user['username']} stats will be calculated");
            $output->writeln(date('Y-m-d H:i:s') . ' Twitter...');
            $this->updateTwitterStats($user['_id'], $user['accounts']['twitter']['user_name']);
            $output->writeln(date('Y-m-d H:i:s').' Instagram...');
            $this->updateInstagramStats($user['_id'], $user['accounts']['instagram']['user_name']);
        }
        $output->writeln(date('Y-m-d H:i:s') . ' Command "user:op:update_all" executed.');
    }

    private function getLatestsAll(InputInterface $input, OutputInterface $output)
    {

        $output->writeln(date('Y-m-d H:i:s') . ' Command "user:op:latest_all" execution started.');
        $users = $this->userModel->getActiveUsers();

        foreach ($users['data'] as $user) {
            $output->writeln(date('Y-m-d H:i:s')
                . ' Fetching ' . $user['accounts']['twitter']['user_name'] . ' tweets...');

            $command = $this->getApplication()->find('user:op');
            $arguments = array(
                'command'   => 'user:op',
                'operation'  => 'latest_posts',
                '--twitter'  => $user['accounts']['twitter']['user_id']
            );
            $userStatsInput = new ArrayInput($arguments);

            $returnCode = $command->run($userStatsInput, $output);
            $output->writeln(date('Y-m-d H:i:s') . 'Fetching '
                . $user['accounts']['twitter']['user_name'] . ' instagram medias...');

            $command = $this->getApplication()->find('user:op');
            $arguments = array(
                'command'   => 'user:op',
                'operation'  => 'latest_posts',
                '--instagram'  => $user['accounts']['instagram']['user_id']
            );
            $userStatsInput = new ArrayInput($arguments);
            $returnCode = $command->run($userStatsInput, $output);
        }
        $output->writeln(date('Y-m-d H:i:s') . 'Command "user:op:latest_all" executed.');

    }
}