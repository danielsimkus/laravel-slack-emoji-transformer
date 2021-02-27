<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer;

use DanielSimkus\SlackEmojiTransformer\Actions\LoadCustomEmojis;
use DanielSimkus\SlackEmojiTransformer\Actions\LoadDefaultEmojis;
use DanielSimkus\SlackEmojiTransformer\Transformers\TransformsUrls;
use Illuminate\Support\Collection;

final class SlackEmojiTransformerService
{
    private string $token;

    public function __construct(
        private LoadDefaultEmojis $defaultEmojiLoader,
        private LoadCustomEmojis $customEmojiLoader,
        private TransformsUrls $slackUrlTransformer
    ) {}

    public function setBotToken($token): self
    {
        $this->token = $token;
        return $this;
    }

    public function transform(string $message): string
    {
        return $this->getReplacements($message)
            ->reduce(fn ($message, $replacement) => str_ireplace($replacement, $message));
    }

    /**
     * @param string $message
     * @return Collection A collection of replacements to apply ['from'=> ':this:', 'to' => '&#x144d', 'from'=> ':this:', 'to' => 'https://urltoemoji.com/image.png', ]
     */
    public function getReplacements(string $message): Collection
    {
        preg_match_all('/:[^:\s]*(?:::[^:\s]*)*:/', $message, $emojis);
        if (!$emojis) {
            return collect([]);
        }
        $emojis = $emojis[0];
        $replacements = collect([]);
        $customEmojis = app(LoadCustomEmojis::class)->load($this->token, $this->isBot);
        $defaultEmojis = app(LoadDefaultEmojis::class)->load();
        foreach ($emojis as $emoji) {
            $strippedEmoji = str_replace(':', '', $emoji);
            if ($customEmojis->has($strippedEmoji)) {
                $replacements->add(['from' => $emoji, 'to' => $this->slackUrlTransformer->transform($customEmojis->get($strippedEmoji))]);
            } elseif ($defaultEmoji = $defaultEmojis->first(fn ($item) => $item['name'] === $strippedEmoji)) {
                $replacements->add(['from' => $emoji, 'to' => '&#x' . $defaultEmoji['unicode'] . ';']);
            }
        }
        return $replacements;
    }

}