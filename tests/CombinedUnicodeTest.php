<?php

namespace DanielSimkus\SlackEmojiTransformer\Tests;

use DanielSimkus\SlackEmojiTransformer\SlackEmojiTransformerService;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;

class CombinedUnicodeTest extends TestCase
{
    /** @test */
    public function it_combines_heart_chars_correctly()
    {
        $heart = ':heart:';
        $expected = '&#x2764;&#xfe0f;';
        $transformer = $this->getTransformerService();

        $actual = $transformer->transform($heart);

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function it_can_find_an_alias_within_multiple_aliases()
    {
        $emojiMap = <<<MAP
[
{
    "name": "+1",
    "unicode": "1f44d",
    "skinVariations": {
      "2": { "name": "+1::skin-tone-2", "unicode": "1f44d-1f3fb" },
      "3": { "name": "+1::skin-tone-3", "unicode": "1f44d-1f3fc" },
      "4": { "name": "+1::skin-tone-4", "unicode": "1f44d-1f3fd" },
      "5": { "name": "+1::skin-tone-5", "unicode": "1f44d-1f3fe" },
      "6": { "name": "+1::skin-tone-6", "unicode": "1f44d-1f3ff" }
    },
    "id": "E1f44d",
    "keywords": ["+1", "hand", "thumb", "up", "thumbsup", "y", "yes", "people", "person"],
    "aliases": [
        {"name": "bigthumb", "displayName": "bigthumb"},
        {"name": "thumbsup", "displayName": "thumbsup"}
    ]
  }
  ]
MAP;

        $map = collect(json_decode($emojiMap, true));
        $transformer = $this->getTransformerService();

        $unicode = $transformer->getDefaultUnicodeEmoji($map, 'thumbsup');
        $this->assertNotNull($unicode);

        $unicode = $transformer->getDefaultUnicodeEmoji($map, 'bigthumb');
        $this->assertNotNull($unicode);

        $unicode = $transformer->getDefaultUnicodeEmoji($map, 'bucket');
        $this->assertNull($unicode);
    }

    /** @test */
    public function it_combines_skin_variations_correctly()
    {
        $emoji = 'Hey :+1::skin-tone-5:';
        $expected = 'Hey &#x1f44d;&#x1f3fe;';
        $transformer = $this->getTransformerService();
        $actual = $transformer->transform($emoji);

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function it_combines_skin_variations_with_alias_correctly()
    {
        $emoji = 'Hey :thumbsup::skin-tone-5:';
        $expected = 'Hey &#x1f44d;&#x1f3fe;';
        $transformer = $this->getTransformerService();
        $actual = $transformer->transform($emoji);

        $this->assertEquals($expected, $actual);
    }
}