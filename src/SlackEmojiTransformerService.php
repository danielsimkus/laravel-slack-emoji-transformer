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
            ->reduce(fn ($mesage, $replacements) => str_ireplace($replacements['from'], $replacements['to'], $message));
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
        $defaultEmojis = $this->defaultEmojiLoader->load();
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

                if ($skinVariant && $this->applySkinVariation($defaultEmoji, $emoji, $skinVariant, $replacements)) {
                    continue;
                }

                $replacements->add([
                    'from' => $emoji,
                    'to' => $this->combineUnicodeEmojis(explode('-', $defaultEmoji['unicode']))
                ]);
            }
        }
        return $replacements;
    }

    private function combineUnicodeEmojis(array $unicodeEmojis)
    {
        return '&#x' . implode(';&#x', $unicodeEmojis) . ';';
    }

    public function getDefaultUnicodeEmoji(Collection $defaultEmojis, string $emojiName): ?array
    {
        $directMatch =  $defaultEmojis->first(fn($item) => $item['name'] === $emojiName);

        if ($directMatch) {
            return $directMatch;
        }

        return $defaultEmojis
            ->first(fn($item) => array_key_exists('aliases', $item) && collect($item['aliases'])->where('name', $emojiName)->isNotEmpty());
    }

    private function applySkinVariation(array $emojiArray, string $emoji, string $skinVariant, Collection $replacements): bool
    {
        if (!array_key_exists('skinVariations', $emojiArray)) {
            return false;
        }

        $skinVariants = $emojiArray['skinVariations'];
        $variantUnicode = $this->findUnicodeFromSkinVariations($skinVariants, $skinVariant);
        if ($variantUnicode->isNotEmpty()) {
            $replacements->add([
                'from' => $emoji,
                'to' => $this->combineUnicodeEmojis(explode('-', $variantUnicode->first()['unicode']))
            ]);
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
            $customEmojis = $this->customEmojiLoader->load($this->token);
        }

        return $customEmojis;
    }

    private function findUnicodeFromSkinVariations(mixed $skinVariants, string $skinVariant): Collection
    {
        $variantUnicode = collect(
            array_filter(
                $skinVariants,
                fn($v) => str_ends_with($v['name'], $skinVariant)
            )
        );

        return $variantUnicode;
    }

}
