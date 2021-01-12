<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer\Actions;

use Illuminate\Hashing\HashManager;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Filesystem\Cache as Cache;
use Illuminate\Config\Repository as Config;

class LoadCustomEmojis
{
    private Http $http;
    private Cache $cache;
    private Config $config;
    private HashManager $hashManager;

    public function __construct(
        Http $http,
        Cache $cache,
        Config $config,
        HashManager $hashManager
    ) {
        $this->http = $http;
        $this->cache = $cache;
        $this->config = $config;
        $this->hashManager = $hashManager;
    }

    public function load(string $token)
    {
        return $this->cache->remember(
            static::class . $this->hashManager->make($token),
            $this->config->get('slack-emoji-transformer.custom-emoji-cache-time-seconds', 300),
            fn () => $this->loadEmojisFromSlack($token)
        );
    }

    protected function loadEmojisFromSlack($token) {
        return $this->http->withToken($token)
            ->get($this->config->get('slack-emoji-transformer.slack-api', "https://slack.com/api/") . 'emoji.list')->json();
    }
}
