<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "php-doc-block-header-fixer".
 *
 * Copyright (C) 2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace KonradMichalik\PhpDocBlockHeaderFixer\Tests\Enum;

use KonradMichalik\PhpDocBlockHeaderFixer\Enum\Separate;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(Separate::class)]
final class SeparateTest extends TestCase
{
    public function testEnumCases(): void
    {
        $cases = Separate::cases();

        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertCount(4, $cases);
        self::assertContains(Separate::Top, $cases);
        self::assertContains(Separate::Bottom, $cases);
        self::assertContains(Separate::Both, $cases);
        self::assertContains(Separate::None, $cases);
    }

    public function testEnumValues(): void
    {
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame('top', Separate::Top->value);
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame('bottom', Separate::Bottom->value);
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame('both', Separate::Both->value);
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame('none', Separate::None->value);
    }

    public function testGetListReturnsAllValues(): void
    {
        $list = Separate::getList();

        self::assertSame(['top', 'bottom', 'both', 'none'], $list);
    }

    public function testGetListReturnsNonEmptyList(): void
    {
        $list = Separate::getList();

        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertNotEmpty($list);
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertIsArray($list);

        foreach ($list as $key => $value) {
            /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
            self::assertIsInt($key);
            /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
            self::assertIsString($value);
        }
    }

    public function testFromString(): void
    {
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Top, Separate::from('top'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Bottom, Separate::from('bottom'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Both, Separate::from('both'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::None, Separate::from('none'));
    }

    public function testTryFromString(): void
    {
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Top, Separate::tryFrom('top'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Bottom, Separate::tryFrom('bottom'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::Both, Separate::tryFrom('both'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertSame(Separate::None, Separate::tryFrom('none'));

        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertNull(Separate::tryFrom('invalid'));
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertNull(Separate::tryFrom(''));
    }

    public function testFromInvalidStringThrowsException(): void
    {
        $this->expectException(ValueError::class);

        Separate::from('invalid');
    }
}
