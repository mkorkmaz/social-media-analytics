<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Abraham\TwitterOAuth\TwitterOAuth;
use Twitter\Command as t;
use Instagram\Command as i;
use Users\Command as u;
use Symfony\Component\Console\Application;
/**
 * @param array $config
 * @return ServiceProvider
 */
function bootstrap(array $config)
{
    $logger = new Logger('name');
    $logger->pushHandler(new StreamHandler($config['blue_file'], Logger::WARNING));
    $logger->pushHandler(new StreamHandler($config['blue_file'], Logger::INFO));
    $config['db_name'] = $config['elasticsearch'];
    $client = Elasticsearch\ClientBuilder::create()->setHosts($config['elasticsearch']['hosts'])->build();
    $soupmixElasticsearch =  new Soupmix\ElasticSearch(['db_name' => $config['elasticsearch']['db_name']], $client);
    $twitterConnection = new TwitterOAuth(
        $config['twitter']['api_key'],
        $config['twitter']['api_secret'],
        $config['twitter']['access_token'],
        $config['twitter']['access_token_secret']
    );

    /**
     * Set Provider
     */
    $provider = ServiceProvider::getInstance();
    $provider->set('config', $config);
    $provider->set(Monolog\Logger::class, $logger);
    $provider->set(TwitterOAuth::class, $twitterConnection);
    $provider->set(Soupmix\ElasticSearch::class, $soupmixElasticsearch);
    $provider->set(Instagram\Service::class, Instagram\Service::factory($provider));
    $provider->set(Twitter\Service::class, Twitter\Service::factory($provider));
    return $provider;
}
/**
 * Set Application, Commands and then run application
 * @param string $name
 * @param string $version
 * @return Application
 */
function getApplication(string $name=null, string $version=null)
{
    $application = new Application($name, $version);

    // User Commands
    $application->add(new u\OperationsCommand());

    // Twitter Commands
    $application->add(new t\UserStatsCommand());
    $application->add(new t\LatestPostsCommand());
    $application->add(new t\TweetStatsCommand());


    // Instagram Commands
    $application->add(new i\UserStatsCommand());
    $application->add(new i\LatestPostsCommand());

    return $application;
}