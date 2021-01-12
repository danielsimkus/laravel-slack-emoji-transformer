<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer\Actions;

use Illuminate\Hashing\HashManager;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Filesystem\Cache as Cache;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Collection;

class LoadDefaultEmojis
{
    private Cache $cache;
    private Config $config;

    public function __construct(
        Cache $cache,
        Config $config
    ) {
        $this->cache = $cache;
        $this->config = $config;
    }

    public function load(): Collection
    {
        return $this->cache->remember(
            static::class,
            $this->config->get('slack-emoji-transformer.default-emoji-cache-time-seconds', 86400),
            fn () => collect($this->loadEmojisFromSlack($token, $isBot))
        );
    }

    protected function loadEmojisFromFile(): array
    {
        return json_decode(file_get_contents(__DIR__ . '../../data/slack-emoji-map.json'), true);
    }
}
