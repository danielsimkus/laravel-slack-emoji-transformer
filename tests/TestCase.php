<?php

namespace DanielSimkus\SlackEmojiTransformer\Tests;

use DanielSimkus\SlackEmojiTransformer\SlackEmojiTransformerService;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function getTransformerService()
    {
        $cache = new CacheRepository(new \Illuminate\Cache\NullStore());
        $config = new ConfigRepository();
        $http = new \Illuminate\Http\Client\Factory();

        $transformer = new SlackEmojiTransformerService(
            new \DanielSimkus\SlackEmojiTransformer\Actions\LoadDefaultEmojis($cache, $config),
            new \DanielSimkus\SlackEmojiTransformer\Actions\LoadCustomEmojis($http, $cache, $config),
            new \DanielSimkus\SlackEmojiTransformer\Transformers\DefaultUrlTransformer(),
        );

        $transformer->setBotToken('sorry I dont have one');

        return $transformer;
    }
}