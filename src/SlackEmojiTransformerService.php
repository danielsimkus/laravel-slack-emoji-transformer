<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer;

use DanielSimkus\SlackEmojiTransformer\Actions\LoadCustomEmojis;
use DanielSimkus\SlackEmojiTransformer\Actions\LoadDefaultEmojis;
use DanielSimkus\SlackEmojiTransformer\Transformers\TransformsUrls;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $emojis = $this->getEmojiesFromMessage($message);
        if (!$emojis) {
            return collect([]);
        }

        $replacements = collect([]);
        $customEmojis = $this->loadCustomEmojies();
        $defaultEmojis = app(LoadDefaultEmojis::class)->load();
        foreach ($emojis as $emoji) {
            $sections = collect(array_filter(explode(':', $emoji)));
            $emojiName = $sections->first();
            if ($emojiName === 'alias') {
                $sections->shift();
                $emojiName = $sections->first();
            }
            $skinVariant = ($sections->count() > 1) ? $sections->last() : null;
            if ($customEmojis->has($emojiName)) {
                $replacements->add(['from' => $emoji, 'to' => $this->slackUrlTransformer->transform($customEmojis->get($emojiName))]);
            } else {
                $defaultEmoji = $this->getDefaultUnicodeEmoji($defaultEmojis, $emojiName);
                if (!$defaultEmoji) {
                    continue;
                }
                if ($skinVariant && $this->applySkinVariation($defaultEmoji, $emoji, $replacements)) {
                    continue;
                }
                $replacements->add(['from' => $emoji, 'to' => '&#x' . $defaultEmoji['unicode'] . ';']);
            }
        }
        return $replacements;
    }

    private function getDefaultUnicodeEmoji(Collection $defaultEmojis, string $emojiName): ?array
    {
        return $defaultEmojis->first(fn($item) => $item['name'] === $emojiName);
    }

    private function applySkinVariation(array $emojiArray, string $emoji, Collection $replacements): bool
    {
        if (!array_key_exists('skinVariations', $emojiArray)) {
            return false;
        }
        $skinVariants = $emojiArray['skinVariations'];
        $variantUnicode = $this->findUnicodeFromSkinVariations($skinVariants, $emoji);
        if ($variantUnicode->isNotEmpty()) {
            $replacements->add(['from' => $emoji, 'to' => array_reduce(explode('-', $variantUnicode->first()['unicode']), fn ($carry, $unicode) => $carry .= '&#x' . $unicode. ';', '')]);
            return true;
        }
        return false;
    }

    private function getEmojiesFromMessage(string $message): ?array
    {
        preg_match_all('/:[^:\s]*(?:::[^:\s]*)*:/', $message, $emojis);
        return !empty($emojis) ? $emojis[0] :null ;
    }

    private function loadCustomEmojies(): Collection
    {
        $customEmojis = collect([]);
        if ($this->token) {
            $customEmojis = app(LoadCustomEmojis::class)->load($this->token);
        }

        return $customEmojis;
    }

    private function findUnicodeFromSkinVariations(mixed $skinVariants, string $emoji): Collection
    {
        $variantUnicode = collect(array_filter($skinVariants, fn($v) => $v['name'] === ltrim(rtrim($emoji, ":"), ":")));

        return $variantUnicode;
    }

}
