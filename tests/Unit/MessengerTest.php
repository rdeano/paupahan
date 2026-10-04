<?php

namespace Tests\Unit;

use App\Support\Messenger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MessengerTest extends TestCase
{
    public static function inputs(): array
    {
        return [
            ['juan.delacruz', 'juan.delacruz'],
            ['@juan.delacruz', 'juan.delacruz'],
            ['m.me/juan.delacruz', 'juan.delacruz'],
            ['https://m.me/juan.delacruz', 'juan.delacruz'],
            ['https://www.facebook.com/juan.delacruz/', 'juan.delacruz'],
            ['https://web.facebook.com/juan.delacruz?mibextid=abc', 'juan.delacruz'],
            ['  JuanDelaCruz  ', 'JuanDelaCruz'],
            ['', null],
            [null, null],
            ['juan dela cruz', null],
            ['https://example.com/juan', null],
        ];
    }

    #[DataProvider('inputs')]
    public function test_normalize(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Messenger::normalize($input));
    }
}
