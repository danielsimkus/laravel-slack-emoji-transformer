<?php

use DanielSimkus\SlackEmojiTransformer\Transformers\DefaultUrlTransformer;

return [
    'custom-emoji-cache-time-seconds' => 300,
    'default-emoji-cache-time-seconds' => 86400,
    'slack-api' => "https://slack.com/api/",
    'url-transformer' => DefaultUrlTransformer::class
];