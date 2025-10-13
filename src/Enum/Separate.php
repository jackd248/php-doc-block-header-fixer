<?php

declare(strict_types=1);

/*
 * This file is part of the "php-doc-block-header-fixer" Composer package.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\PhpDocBlockHeaderFixer\Enum;

/**
 * Separate.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
enum Separate: string
{
    case Top = 'top';
    case Bottom = 'bottom';
    case Both = 'both';
    case None = 'none';

    /**
     * @return non-empty-list<string>
     */
    public static function getList(): array
    {
        return array_column(self::cases(), 'value');
    }
}
