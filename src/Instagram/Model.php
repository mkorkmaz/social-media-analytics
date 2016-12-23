<?php

declare(strict_types = 1);

namespace Instagram;

use ServiceProvider;
use Soupmix\ElasticSearch;
use Monolog;


class Model
{
    private $service;
    private $db;
    private $logger;

    public function __construct(Service $service, ElasticSearch $db, Monolog\Logger $logger)
    {
        $this->service = $service;
        $this->db = $db;
        $this->logger = $logger;
    }

    public static function factory(ServiceProvider $provider)
    {
        return new Model($provider->get( Service::class), $provider->get(ElasticSearch::class), $provider->get(Monolog\Logger::class));
    }

    public function getUsernameById(string $user_id)
    {

        $user = $this->db->find(
            'users',
            ['accounts.instagram.user_id' => $user_id],
            ['accounts.instagram.user_name'],
            null,
            0,
            1
        );
        return $user['data']['accounts']['instagram']['user_name'];
    }

    public function processLatestMedias(string $user_id, array $medias)
    {
        $now  = microtime();
        foreach ($medias as $media) {
            $is_exists = $this->db->find(
                'posts',
                ['post_id' => $media['id'], 'post_type' => 'insta'],
                ['id'],
                null,
                0,
                1
            );
            $doc = [
                'timestamp' => (int) $now,
                'user_id'   => (string) $user_id,
                'post_id'   => (string) $media['id'],
                'post_type' => 'insta',
                'like_count'    => (int) $media['likesCount'],
                'repost_count'  => 0,
                'is_active'     => 1,
                'comment_count' => (int) $media['commentsCount'],
                'interaction_count' => $media['likesCount'] + $media['commentsCount'],
                'legacy'        => [
                    'insta' => $media
                ]
            ];
            if ($is_exists['total'] === 0) {
                $this->db->insert('posts', $doc);
            }
            else {
                unset($doc['timestamp']);
                $this->db->update('posts',['_id' => $is_exists['data']['_id']], $doc);
            }
            /* removed for later use
            unset($doc['legacy']);
            $doc['timestamp'] = time()*100;
            $this->db->insert('posts_log', $doc);
            */
        }
    }

    public function getLatestPosts(string $username){
        return $this->service->getLatestPosts($username);
    }

    public function getUserData(string $username)
    {
        $userInfo = $this->service->getUserData($username);
        $userStats = [
            'user_id'           => (string) $userInfo->id,
            'user_name'         => (string) $username,
            'user_display_name' => (string) $userInfo->fullName,
            'follower_count'    => (int) $userInfo->followedByCount,
            'following_count'   => (int) $userInfo->followsCount,
            'post_count'        => (int) $userInfo->mediaCount
        ];
        return $userStats;
    }
}