<?php

declare(strict_types = 1);

namespace Users;


use ServiceProvider;
use Soupmix\ElasticSearch;
use Monolog;


class Model
{
    private $db;
    private $logger;

    public function __construct(ElasticSearch $db, Monolog\Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public static function factory(ServiceProvider $provider)
    {
        return new Model($provider->get(ElasticSearch::class), $provider->get(Monolog\Logger::class));
    }

    public function add(string $username, string $twitterUserName = null, string $instagramUserName=null)
    {
        $doc = [
            'username'      => $username,
            'accounts'      => [],
            'is_active'     => 1,
            'is_deleted'    => 0,
            'created_at'    => time() * 100 // ES timestamp uses microseconds
        ];

        if (!empty($twitterUserName)) {
            $doc['accounts']['twitter'] = [
                'user_id'           => (string) 0,
                'user_name'         => (string) $twitterUserName,
                'user_display_name' => (string) "",
                'follower_count'    => 0,
                'following_count'   => 0,
                'post_count'        => 0,
                'interaction_count' => 0
            ];
        }

        if (!empty($instagramUserName)) {
            $doc['accounts']['instagram'] = [
                'user_id'           => (string) 0,
                'user_name'         => (string) $instagramUserName,
                'user_display_name' => (string) "",
                'follower_count'    => 0,
                'following_count'   => 0,
                'post_count'        => 0,
                'interaction_count' => 0
            ];
        }
        $user_id =  $this->db->insert('users', $doc);
        sleep(1); // Make sure inserted data is live
        return $user_id;
    }

    public function updateTwitterStats(string $user_id, array $data)
    {
        $doc = [];
        $doc['accounts']['twitter']['user_id']          = $data['user_id'];
        $doc['accounts']['twitter']['user_name']        = $data['user_name'];
        $doc['accounts']['twitter']['user_display_name']= $data['user_display_name'];
        $doc['accounts']['twitter']['follower_count']   = $data['follower_count'];
        $doc['accounts']['twitter']['following_count']  = $data['following_count'];
        $doc['accounts']['twitter']['post_count']       = $data['post_count'];

        $interactionsCount = $this->getInteractionsCount('tweet', $data['user_id']);
        $doc['accounts']['twitter']['interaction_count']       = $interactionsCount;
        $this->db->update('users', ['_id' => $user_id], $doc);
        $log = $doc['accounts']['twitter'];
        $log['timestamp'] = time()*100;
        $log['sm'] = 'twitter';
        $log['uid'] = $user_id;
        $this->db->insert('users_log', $log);
    }

    public function updateInstagramStats(string $user_id, array $data)
    {
        $doc = [];
        $doc['accounts']['instagram']['user_id']          = $data['user_id'];
        $doc['accounts']['instagram']['user_name']        = $data['user_name'];
        $doc['accounts']['instagram']['user_display_name'] = $data['user_display_name'];
        $doc['accounts']['instagram']['follower_count']   = $data['follower_count'];
        $doc['accounts']['instagram']['following_count']  = $data['following_count'];
        $doc['accounts']['instagram']['post_count']       = $data['post_count'];
        $interactionsCount = $this->getInteractionsCount('insta', $data['user_id']);
        $doc['accounts']['instagram']['interaction_count']       = $interactionsCount;
        $this->db->update('users', ['_id' => $user_id], $doc);
        $log = $doc['accounts']['instagram'];
        $log['timestamp'] = time()*100;
        $log['sm'] = 'instagram';
        $log['uid'] = $user_id;
        $this->db->insert('users_log', $log);
    }

    private function getInteractionsCount(string $platform, string $user_id)
    {
        $db = $this->db->getConnection();
        $params = [];
        $params['index'] = 'sm_stats';
        $params['type'] = 'posts';
        $params['size'] = 1;
        $params['body'] = [
            'query' => [
                'bool' => [
                    'filter' => [
                        ['term' => ['post_type' => $platform]],
                        ['term' => ['user_id' => $user_id]],
                        ['term' => ['is_active' => 1]]

                    ]
                ]
            ]
        ];
        $params['body']['aggs'] = [
            'interaction_count' => ['sum' => ['field' => 'interaction_count']]
        ];
        $results = $db->search($params);
        return (int) $results['aggregations']['interaction_count']['value'];
    }

    public function getActiveUsers()
    {
        return $this->db->find('users', ['is_active' => 1, 'is_deleted' => 0], null, null, 0, 1000 );
    }
}