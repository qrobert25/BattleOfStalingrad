<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis AS iRedis;

class RedisDB
{
    public function set(String $key, String $value, Int $expiration = 0)
    {
        return iRedis::set($key, $value);

        if ($expiration > 0) {
            return $this->expire($key, $expiration);
        }
    }

    public function expire(String $key, Int $expiration)
    {
        return iRedis::expire($key, $expiration);
    }
    
    public function get(String $key)
    {
        return iRedis::get($key);
    }

    public function delete(String $key)
    {
        return iRedis::del($key);
    }
}