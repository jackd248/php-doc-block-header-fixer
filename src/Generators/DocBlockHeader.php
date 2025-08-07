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

namespace KonradMichalik\PhpDocBlockHeaderFixer\Generators;

use InvalidArgumentException;
use KonradMichalik\PhpDocBlockHeaderFixer\Enum\Separate;

use function is_string;

final class DocBlockHeader implements Generator
{
    private function __construct(
        /** @var array<string, string|array<string>> */
        public readonly array $annotations,
        public readonly bool $preserveExisting,
        public readonly Separate $separate,
    ) {}

    /**
     * @param array<string, string|array<string>> $annotations
     */
    public static function create(
        array $annotations,
        bool $preserveExisting = true,
        Separate $separate = Separate::Both,
    ): self {
        self::validateAnnotations($annotations);

        return new self($annotations, $preserveExisting, $separate);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function __toArray(): array
    {
        return [
            'KonradMichalik/docblock_header_comment' => [
                'annotations' => $this->annotations,
                'preserve_existing' => $this->preserveExisting,
                'separate' => $this->separate->value,
            ],
        ];
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private static function validateAnnotations(array $annotations): void
    {
        $allowedAnnotations = [
            'author', 'copyright', 'license', 'version', 'since', 'package', 'subpackage',
            'see', 'link', 'todo', 'fixme', 'deprecated', 'internal', 'api', 'category',
            'example', 'ignore', 'uses', 'used-by', 'throws', 'method', 'property',
            'property-read', 'property-write', 'param', 'return', 'var', 'global',
            'static', 'final', 'abstract',
        ];

        foreach ($annotations as $key => $value) {
            // PHPStan knows $key is string from PHPDoc, but we still validate at runtime
            /* @phpstan-ignore-next-line function.alreadyNarrowedType */
            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf('Annotation key must be a string, %s given', gettype($key)));
            }

            if (empty(trim($key))) {
                throw new InvalidArgumentException('Annotation key cannot be empty');
            }

            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $key)) {
                throw new InvalidArgumentException(sprintf('Invalid annotation key "%s". Must start with letter and contain only letters, numbers, underscore, or dash.', $key));
            }

            if (!in_array($key, $allowedAnnotations, true)) {
                throw new InvalidArgumentException(sprintf('Unknown annotation "%s". Allowed annotations: %s', $key, implode(', ', $allowedAnnotations)));
            }
        }
    }
}
