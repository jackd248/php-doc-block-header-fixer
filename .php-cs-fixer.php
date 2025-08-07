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

use EliasHaeussler\PhpCsFixerConfig\Config;
use EliasHaeussler\PhpCsFixerConfig\Package;
use EliasHaeussler\PhpCsFixerConfig\Rules;
use Symfony\Component\Finder;

$header = Rules\Header::create(
    'php-doc-block-header-fixer',
    Package\Type::ComposerPackage,
    Package\Author::create('Konrad Michalik', 'hej@konradmichalik.dev'),
    Package\CopyrightRange::from(2025),
    Package\License::GPL3OrLater,
);

return Config::create()
    ->withRule($header)
//    ->withRule(
//        RuleSet::fromArray(
//            DocBlockHeader::create(
//                [
//                    'author' => 'Konrad Michalik <hej@konradmichalik.dev>',
//                    'license' => 'GPL-3.0-or-later',
//                    'package' => 'PhpDocBlockHeaderFixer',
//                ]
//            )->__toArray()
//        )
//    )
//     ->registerCustomFixers([new KonradMichalik\PhpDocBlockHeaderFixer\Rules\DocBlockHeaderFixer()]) // Temporarily disabled
    ->withFinder(static fn (Finder\Finder $finder) => $finder
        ->in(__DIR__)
        ->exclude('vendor'),
    )
;
