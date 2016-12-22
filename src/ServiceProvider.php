<?php


class ServiceProvider
{
    private static $instance = null;

    private static $registry = [];

    private $provides = [
        'config' => 'array',
        Instagram\Service::class => Instagram\Service::class,
        Twitter\Service::class => Twitter\Service::class,
        Monolog\Logger::class => Monolog\Logger::class,
        Soupmix\ElasticSearch ::class => Soupmix\ElasticSearch ::class,
        Abraham\TwitterOAuth\TwitterOAuth::class => Abraham\TwitterOAuth\TwitterOAuth::class
    ];

    private function  __construct() { }

    private function  __clone() { }

    public static function getInstance()
    {
        if (!(self::$instance instanceof ServiceProvider)) {
            self::$instance = new ServiceProvider();
        }
        return self::$instance;
    }

    public function set(string $key, $value)
    {
        if (!in_array($key, array_keys($this->provides))) {
            throw new InvalidArgumentException(sprintf('%s is not valid provides', $key));
        }
        $keyType = $this->provides[$key];
        $valueType = gettype($value);
        if($valueType === 'object' && (!$value instanceof $keyType)){
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        if ($valueType !== 'object' && $valueType !== $keyType) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        self::$registry[$key] = $value;
    }

    public function has(string $key)
    {
        return array_key_exists($key, self::$registry);
    }

    public function get(string $key)
    {
        $value =  self::$registry[$key] ?? null;
        $keyType = $this->provides[$key];
        $valueType = gettype($value);
        if($valueType === 'object' && (!$value instanceof $keyType)) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        if ($valueType !== 'object' && $valueType !== $keyType) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $valueType));
        }
        return $value;
    }
}
