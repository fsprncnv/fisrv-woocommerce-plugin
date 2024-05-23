<?php

use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase
{
    public function testStringToId(): void
    {
        $original = "Button Content";
        $id = self::strp($original) . '_id';

        $this->assertEquals($id, 'button_content_id');
    }

    private static function strp(string $str): string
    {
        $str = str_replace(' ', '_', $str);
        return strtolower($str);
    }
}
