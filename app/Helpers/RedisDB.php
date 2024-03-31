<?php

namespace App\Helpers;

class RedisDB
{
    private $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('localhost', 6379);
    }

    public function set(String $key, String $value, Int $expiration = 0)
    {
        return $this->redis->set($key, $value);

        if ($expiration > 0) {
            return $this->expire($key, $expiration);
        }
    }

    public function expire(String $key, Int $expiration)
    {
        return $this->redis->expire($key, $expiration);
    }
    
    public function get(String $key)
    {
        return $this->redis->get($key);
    }

    public function delete(String $key)
    {
        return $this->redis->del($key);
    }
}