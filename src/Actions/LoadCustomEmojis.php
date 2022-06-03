<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer\Actions;

use Illuminate\Hashing\HashManager;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;

class LoadCustomEmojis
{
    private Http $http;
    private Cache $cache;
    private Config $config;
    private HashManager $hashManager;

    public function __construct(
        Http $http,
        Cache $cache,
        Config $config
    ) {
        $this->http = $http;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function load(string $token): Collection
    {
        return $this->cache->remember(
            static::class . sha1($token),
            $this->config->get('slack-emoji-transformer.custom-emoji-cache-time-seconds', 300),
            fn () => $this->loadEmojisFromSlack($token)
        );
    }

    protected function loadEmojisFromSlack(string $token): Collection
    {
        $action = 'emoji.list';
        $response = $this->http->withToken($token)
            ->get($this->config->get('slack-emoji-transformer.slack-api', "https://slack.com/api/") . $action)
            ->json();

        if ($response['ok'] === false) {
//            Throw new \Exception('Failed to load custom emojis: ' . $response['error']);
            return collect([]);
        }

        $emojies = collect($response['emoji']);
        return $emojies;
    }
}
