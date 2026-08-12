<?php

namespace Tests\Unit\Services\Recharge;

use App\Services\Recharge\MokshiqCircleMap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MokshiqCircleMapTest extends TestCase
{
    #[DataProvider('circles')]
    public function test_normalize(string $input, string $expected): void
    {
        $this->assertSame($expected, MokshiqCircleMap::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function circles(): array
    {
        return [
            'operator_fetch_full' => ['Bihar and Jharkhand', 'Bihar Jharkhand'],
            'already_mokshiq' => ['Bihar Jharkhand', 'Bihar Jharkhand'],
            'short_bihar' => ['Bihar', 'Bihar Jharkhand'],
            'extra_spaces' => ['  Bihar   and   Jharkhand  ', 'Bihar Jharkhand'],
            'generic_and' => ['Madhya and Pradesh', 'Madhya Pradesh'],
            'delhi' => ['Delhi NCR', 'Delhi NCR'],
        ];
    }
}
