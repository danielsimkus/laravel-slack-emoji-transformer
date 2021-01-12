<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer;

use Illuminate\Support\Collection;

class SlackEmojiTransformerService
{
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
        return Collection([]);
    }
}