<?php
namespace DanielSimkus\SlackEmojiTransformer\Providers;

use DanielSimkus\SlackEmojiTransformer\Transformers\DefaultUrlTransformer;
use DanielSimkus\SlackEmojiTransformer\Transformers\TransformsUrls;
use Illuminate\Support\ServiceProvider;

class SlackEmojiTransformerProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../configs/config.php' => config_path('slack-emoji-transformer.php'),
        ]);

        $this->app->bind(TransformsUrls::class, config('slack-emoji-transformer.url-transformer', DefaultUrlTransformer::class));
    }
}