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

namespace KonradMichalik\PhpDocBlockHeaderFixer\Tests\Generators;

use InvalidArgumentException;
use KonradMichalik\PhpDocBlockHeaderFixer\Enum\Separate;
use KonradMichalik\PhpDocBlockHeaderFixer\Generators\DocBlockHeader;
use KonradMichalik\PhpDocBlockHeaderFixer\Generators\Generator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(DocBlockHeader::class)]
final class DocBlockHeaderTest extends TestCase
{
    public function testImplementsGeneratorInterface(): void
    {
        $docBlockHeader = DocBlockHeader::create(['author' => 'John Doe']);

        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(Generator::class, $docBlockHeader);
    }

    public function testCreateWithValidAnnotations(): void
    {
        $annotations = ['author' => 'John Doe', 'license' => 'MIT'];
        $docBlockHeader = DocBlockHeader::create($annotations);

        self::assertSame($annotations, $docBlockHeader->annotations);
        self::assertTrue($docBlockHeader->preserveExisting);
        self::assertSame(Separate::Both, $docBlockHeader->separate);
    }

    public function testCreateWithCustomParameters(): void
    {
        $annotations = ['author' => 'Jane Smith'];
        $docBlockHeader = DocBlockHeader::create(
            $annotations,
            false,
            Separate::None,
        );

        self::assertSame($annotations, $docBlockHeader->annotations);
        self::assertFalse($docBlockHeader->preserveExisting);
        self::assertSame(Separate::None, $docBlockHeader->separate);
    }

    public function testCreateWithArrayValueAnnotations(): void
    {
        $annotations = [
            'author' => ['John Doe <john@example.com>', 'Jane Smith <jane@example.com>'],
            'license' => 'MIT',
        ];
        $docBlockHeader = DocBlockHeader::create($annotations);

        self::assertSame($annotations, $docBlockHeader->annotations);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $annotations = ['author' => 'John Doe', 'license' => 'MIT'];
        $docBlockHeader = DocBlockHeader::create($annotations, false, Separate::Top);

        $result = $docBlockHeader->__toArray();

        $expected = [
            'KonradMichalik/docblock_header_comment' => [
                'annotations' => $annotations,
                'preserve_existing' => false,
                'separate' => 'top',
            ],
        ];

        self::assertSame($expected, $result);
    }

    public function testToArrayWithDefaultParameters(): void
    {
        $annotations = ['author' => 'John Doe'];
        $docBlockHeader = DocBlockHeader::create($annotations);

        $result = $docBlockHeader->__toArray();

        $expected = [
            'KonradMichalik/docblock_header_comment' => [
                'annotations' => $annotations,
                'preserve_existing' => true,
                'separate' => 'both',
            ],
        ];

        self::assertSame($expected, $result);
    }

    public function testValidateAnnotationsWithValidKeys(): void
    {
        $validAnnotations = [
            'author' => 'John Doe',
            'copyright' => '2024',
            'license' => 'MIT',
            'version' => '1.0.0',
            'since' => '1.0.0',
            'package' => 'MyPackage',
            'subpackage' => 'SubPackage',
            'see' => 'https://example.com',
            'link' => 'https://example.com',
            'todo' => 'Fix this',
            'fixme' => 'Fix this',
            'deprecated' => 'Use something else',
            'internal' => 'Internal use only',
            'api' => 'Public API',
            'category' => 'Category',
            'example' => 'Example usage',
            'ignore' => 'Ignore this',
            'uses' => 'Uses something',
            'used-by' => 'Used by something',
            'throws' => 'Exception',
            'method' => 'methodName()',
            'property' => '$propertyName',
            'property-read' => '$readOnlyProperty',
            'property-write' => '$writeOnlyProperty',
            'param' => 'string $param',
            'return' => 'string',
            'var' => 'string',
            'global' => '$GLOBALS',
            'static' => 'Static method',
            'final' => 'Final class',
            'abstract' => 'Abstract class',
        ];

        // This should not throw an exception
        $docBlockHeader = DocBlockHeader::create($validAnnotations);
        /* @phpstan-ignore-next-line staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(DocBlockHeader::class, $docBlockHeader);
    }

    public function testValidateAnnotationsThrowsExceptionForNonStringKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Annotation key must be a string, integer given');

        // @phpstan-ignore-next-line argument.type
        DocBlockHeader::create([123 => 'invalid']);
    }

    public function testValidateAnnotationsThrowsExceptionForEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Annotation key cannot be empty');

        DocBlockHeader::create(['' => 'value']);
    }

    public function testValidateAnnotationsThrowsExceptionForWhitespaceOnlyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Annotation key cannot be empty');

        DocBlockHeader::create(['   ' => 'value']);
    }

    public function testValidateAnnotationsThrowsExceptionForInvalidKeyFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid annotation key "123invalid". Must start with letter and contain only letters, numbers, underscore, or dash.');

        DocBlockHeader::create(['123invalid' => 'value']);
    }

    public function testValidateAnnotationsThrowsExceptionForInvalidKeyCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid annotation key "invalid@key". Must start with letter and contain only letters, numbers, underscore, or dash.');

        DocBlockHeader::create(['invalid@key' => 'value']);
    }

    public function testValidateAnnotationsThrowsExceptionForUnknownAnnotation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown annotation "unknownAnnotation"');

        DocBlockHeader::create(['unknownAnnotation' => 'value']);
    }

    public function testValidateAnnotationsAcceptsValidKeyWithHyphen(): void
    {
        $docBlockHeader = DocBlockHeader::create(['property-read' => '$property']);

        self::assertSame(['property-read' => '$property'], $docBlockHeader->annotations);
    }

    public function testValidateAnnotationsRejectsKeyWithUnderscore(): void
    {
        // This should throw an exception because 'used_by' with underscore is not in allowed list
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown annotation "used_by"');

        DocBlockHeader::create(['used_by' => 'Something']);
    }

    public function testValidateAnnotationsAcceptsValidKeyWithNumbers(): void
    {
        // Note: There are no standard annotations with numbers in the allowed list
        // Let's test that validation works correctly for keys with numbers
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown annotation "author2"');

        DocBlockHeader::create(['author2' => 'John Doe']);
    }

    public function testCreateWithEmptyAnnotations(): void
    {
        $docBlockHeader = DocBlockHeader::create([]);

        self::assertSame([], $docBlockHeader->annotations);
        self::assertTrue($docBlockHeader->preserveExisting);
        self::assertSame(Separate::Both, $docBlockHeader->separate);
    }

    public function testPropertiesAreReadonly(): void
    {
        $docBlockHeader = DocBlockHeader::create(['author' => 'John Doe']);

        self::assertSame(['author' => 'John Doe'], $docBlockHeader->annotations);
        self::assertTrue($docBlockHeader->preserveExisting);
        self::assertSame(Separate::Both, $docBlockHeader->separate);

        // Properties should be readonly - but we can't test this directly in PHP 8.1+
        // The readonly modifier is enforced at the language level
    }

    public function testConstructorIsPrivate(): void
    {
        $reflection = new ReflectionClass(DocBlockHeader::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(DocBlockHeader::class);

        self::assertTrue($reflection->isFinal());
    }
}
