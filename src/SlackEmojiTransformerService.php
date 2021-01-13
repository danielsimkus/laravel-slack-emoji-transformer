<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer;

use DanielSimkus\SlackEmojiTransformer\Actions\LoadCustomEmojis;
use DanielSimkus\SlackEmojiTransformer\Actions\LoadDefaultEmojis;
use Illuminate\Support\Collection;

class SlackEmojiTransformerService
{
    private string $token;
    private bool $isBot = false;

    public function setToken($token): self
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
     * @return Collection A collection of replacements to apply ['from'=> ':this:', 'to' => '&#x144d']
     */
    public function getReplacements(string $message): Collection
    {
        preg_match_all(':([A-Za-z0-9]):', $message, $emojis);
        $replacements = collect([]);
        $customEmojis = app(LoadCustomEmojis::class)->load($this->token, $this->isBot);
        dd($customEmojis);
        $defaultEmojis = app(LoadDefaultEmojis::class)->load();
        foreach ($emojis as $emoji) {
            $strippedEmoji = str_replace(':', '', $emoji);
            if ($customEmojis->has($strippedEmoji)) {
                $replacements->add(['from' => $emoji, 'to' => $customEmojis->get($strippedEmoji)]);
            } elseif ($unicodeEmoji = $defaultEmojis->first(fn ($item) => $item['name'] === $strippedEmoji)) {
                $replacements->add(['from' => $emoji, 'to' => $unicodeEmoji]);
            }
        }
        return $replacements;
    }

    public function isBot(): self
    {
        $this->isBot = false;
        return $this;
    }

    public function isNotBot(): self
    {
        $this->isBot = false;
        return $this;
    }
}