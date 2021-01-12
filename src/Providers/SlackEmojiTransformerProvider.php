<?php
namespace DanielSimkus\SlackEmojiTransformer\Providers;

use DanielSimkus\SlackEmojiTransformer\SlackEmojiTransformerService;
use Illuminate\Support\ServiceProvider;

class SlackEmojiTransformerProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../configs/config.php' => config_path('slack-emoji-transformer.php'),
        ]);
    }
}