<?php

declare(strict_types = 1);

namespace Twitter;


use ServiceProvider;
use Soupmix\ElasticSearch;
use Monolog;
use DateTime;

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

    public function getUserData(string $username)
    {
        $userInfo = $this->service->getUserData($username);
        $userStats = [
            'user_id'           => (string) $userInfo[0]->id_str,
            'user_name'         => (string) $username,
            'user_display_name' => (string) $userInfo[0]->name,
            'follower_count'    => (int) $userInfo[0]->followers_count,
            'following_count'   => (int) $userInfo[0]->friends_count,
            'post_count'        => (int) $userInfo[0]->statuses_count
        ];
        return$userStats;
    }

    public function processLatestTweets(string $user_id, array $tweets)
    {
        $now  = time() * 100; // epoch_millis
        foreach ($tweets as $tweet) {
            if($tweet['retweeted'] === true) {
                continue;
            }
            $is_exists = $this->db->find(
                'posts',
                    ['post_id' => $tweet['id_str'], 'post_type' => 'tweet'],
                    ['id'],
                    null,
                    0,
                    1
            );
            $doc = [
                'timestamp' => (int) $now,
                'user_id'   => (string) $user_id,
                'post_id'   => (string) $tweet['id_str'],
                'post_type' => 'tweet',
                'is_active' => 1,
                'like_count'    => (int) $tweet['favorite_count'],
                'repost_count'  => (int) $tweet['retweet_count'],
                'interaction_count' => $tweet['favorite_count'] + $tweet['retweet_count'],
                'comment_count' => 0,
                'legacy'        => [
                    'tweet' => $tweet
                ]
            ];
            if ($is_exists['total'] === 0) {
                $this->db->insert('posts', $doc);
                /* removed for later use
                unset($doc['legacy']);
                $this->db->insert('posts_log', $doc);
                */
            }
        }
    }

    public function getLatestPost(string $user_id){
        return $this->service->getLatestPost($user_id);
    }

    public function updateTweetData()
    {
        $aMonthAgoDate = new DateTime('30 days ago');
        $aMonthAgo = $aMonthAgoDate->getTimestamp() * 100; // epoch millis
        $tweets = $this->db->find(
            'posts',
                ['is_active' => 1, 'timestamp__gte' => $aMonthAgo],
                ['post_id'],
                null,
                0 ,
                10000
        );
        $idsBucket=[];
        $bucketIndex = 0;
        for ($i=0; $i<$tweets['total']; $i++ ) {
            $idsBucket[$bucketIndex][] = $tweets['data'][$i]['post_id'];
            if($i%100 === 99){
                $bucketIndex++;
            }
        }
        foreach ($idsBucket as $idBucket) {
            $lookupTweets = $this->service->lookup($idBucket);
            if (property_exists($lookupTweets, 'id')) {
                foreach ($lookupTweets->id as $id_str => $tweet) {
                    if (empty($tweet)) {
                        $this->db->update(
                            'posts',
                                ['post_id' => $id_str, 'post_type' => 'tweet'],
                                ['is_active' => 0]
                        );
                    } else {
                        $doc = [
                            'user_id' => (string) $tweet->user->id_str,
                            'post_id' => (string) $id_str,
                            'post_type' => 'tweet',
                            'like_count' => (int) $tweet->favorite_count,
                            'repost_count' => (int) $tweet->retweet_count,
                            'interaction_count' => $tweet->favorite_count + $tweet->retweet_count,
                            'comment_count' => 0,
                            'legacy' => [
                                'tweet' => $tweet
                            ]
                        ];
                        $this->db->update('posts', ['post_id' => $id_str, 'post_type' => 'tweet'], $doc);
                        unset($doc['legacy']);
                        $doc['timestamp'] = time() * 100; // epoch_millis
                        $this->db->insert('posts_log', $doc);
                    }
                }
            }
        }
    }
}