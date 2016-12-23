<?php
declare(strict_types = 1);

namespace Twitter;

use ServiceProvider;
use Abraham\TwitterOAuth\TwitterOAuth;

class Service
{
    private $config;
    private $connection;

    public function __construct(array $config, TwitterOAuth $connection)
    {
        $this->config = $config;
        $this->connection = $connection;
    }

    public static function factory(ServiceProvider $provider)
    {
        return new Service($provider->get('config'), $provider->get(TwitterOAuth::class));
    }


    public function getUserData(string $username)
    {
        return $this->connection->get('users/lookup', ['screen_name' => $username]);
    }

    public function getUserDataId(string $user_id)
    {
        return $this->connection->get('users/lookup', ['user_id' => $user_id]);
    }

    public function getLatestPost(string $user_id)
    {
        return $this->connection->get(
            'statuses/user_timeline',
            ['count' => 200, 'user_id' => $user_id, 'include_rts' => false, 'exclude_replies' => true]
        );
    }

    /**
     * @param array $idBucket
     * @return array|object
     */
    public function lookup(array $idBucket)
    {
        return $this->connection->get(
            'statuses/lookup',
            [ 'id' => implode(',',$idBucket),'map'=>true]
        );
    }

}