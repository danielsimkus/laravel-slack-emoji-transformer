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
        Config $config,
        HashManager $hashManager
    ) {
        $this->http = $http;
        $this->cache = $cache;
        $this->config = $config;
        $this->hashManager = $hashManager;
    }

    public function load(string $token, bool $isBot = false): Collection
    {
        return $this->cache->remember(
            static::class . $this->hashManager->make($token),
            $this->config->get('slack-emoji-transformer.custom-emoji-cache-time-seconds', 300),
            fn () => $this->loadEmojisFromSlack($token, $isBot)
        );
    }

    protected function loadEmojisFromSlack(string $token, bool $isBot): Collection
    {
        $action = ($isBot) ? 'admin.emoji.list' : 'emoji.list';
        $response = $this->http->withToken($token)
            ->get($this->config->get('slack-emoji-transformer.slack-api', "https://slack.com/api/") . $action)
            ->json();
        if ($response['ok'] === false) {
            Throw new \Exception('Failed to load custom emojis: ' . $response['error']);
        }

        $emojies = collect($response['emoji']);
        if ($isBot) {
            $emojies->map(fn ($item) => $item['url']);
        }
        return $emojies;
    }
}
