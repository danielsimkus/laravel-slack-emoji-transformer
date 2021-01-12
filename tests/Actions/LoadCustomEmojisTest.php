<?php

declare(strict_types=1);

namespace SlackEmojiTransformer\Tests\Actions;

use PHPUnit\Framework\TestCase;
use DanielSimkus\SlackEmojiTransformer\SlackEmojiTransformerService;

class LoadCustomEmojisTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_loads_custom_emojis_from_slack()
    {
        dd(new SlackEmojiTransformerService());
        app()->bind(LoadCustomEmojis::class, LoadCustomEmojis::class);
        app()->make(LoadCustomEmojis::class)->load('fakeToken');

    }
}