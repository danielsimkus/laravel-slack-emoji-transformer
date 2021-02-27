<?php

declare(strict_types=1);

namespace DanielSimkus\SlackEmojiTransformer\Transformers;

class DefaultUrlTransformer implements TransformsUrls
{
    /*
     * You can bind/overwrite TransformUrls to whatever you require
     * I personally just do a replacement in the Frontend
     */
    public function transform(string $url)
    {
        return $url;
    }
}
