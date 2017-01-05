<?php
declare(strict_types = 1);

namespace Instagram;

use ServiceProvider;
use InstagramScraper\Instagram;

class Service
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function factory(ServiceProvider $provider)
    {
        return new Service($provider->get('config'));
    }

    public function getUserData(string $username)
    {
        return Instagram::getAccount($username);
    }

    public function getUserDataById(string $user_id)
    {
        return Instagram::getAccountById($user_id);
    }

    public function getLatestPosts(string $username)
    {
        $userInfo = Instagram::getAccount($username);
        $medias = Instagram::getMedias($username, 150);

        $nofMedias = count($medias);
        for($i=0; $i < $nofMedias; $i++) {
            $medias[$i]->username = $userInfo->username;
            $medias[$i]->profilePicUrl = $userInfo->profilePicUrl;
        }
        return $medias;
    }
}