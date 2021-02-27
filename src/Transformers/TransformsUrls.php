<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer\Transformers;


interface TransformsUrls
{
    public function transform(string $url);
}
