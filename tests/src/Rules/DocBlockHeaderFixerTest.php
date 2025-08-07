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

namespace KonradMichalik\PhpDocBlockHeaderFixer\Tests\Rules;

use KonradMichalik\PhpDocBlockHeaderFixer\Rules\DocBlockHeaderFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SplFileInfo;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(DocBlockHeaderFixer::class)]
final class DocBlockHeaderFixerTest extends TestCase
{
    private DocBlockHeaderFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new DocBlockHeaderFixer();
    }

    public function testGetDefinition(): void
    {
        $definition = $this->fixer->getDefinition();

        self::assertSame('Add configurable DocBlock annotations before class declarations.', $definition->getSummary());
    }

    public function testGetName(): void
    {
        self::assertSame('KonradMichalik/docblock_header_comment', $this->fixer->getName());
    }

    public function testIsCandidate(): void
    {
        $tokens = Tokens::fromCode('<?php class Foo {}');

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseWhenNoClass(): void
    {
        $tokens = Tokens::fromCode('<?php function foo() {}');

        self::assertFalse($this->fixer->isCandidate($tokens));
    }

    public function testBuildDocBlockWithEmptyAnnotations(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, []);

        self::assertSame("/**\n */", $result);
    }

    public function testBuildDocBlockWithSingleAnnotation(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, ['author' => 'John Doe <john@example.com>']);

        $expected = "/**\n * @author John Doe <john@example.com>\n */\n";
        self::assertSame($expected, $result);
    }

    public function testBuildDocBlockWithMultipleAnnotations(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, [
            'author' => 'John Doe <john@example.com>',
            'license' => 'MIT',
            'package' => 'MyPackage',
        ]);

        $expected = "/**\n * @author John Doe <john@example.com>\n * @license MIT\n * @package MyPackage\n */\n";
        self::assertSame($expected, $result);
    }

    public function testBuildDocBlockWithArrayValue(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, [
            'author' => [
                'John Doe <john@example.com>',
                'Jane Smith <jane@example.com>',
            ],
            'license' => 'MIT',
        ]);

        $expected = "/**\n * @author John Doe <john@example.com>\n * @author Jane Smith <jane@example.com>\n * @license MIT\n */\n";
        self::assertSame($expected, $result);
    }

    public function testSupportsMethod(): void
    {
        $file = new SplFileInfo(__FILE__);

        self::assertTrue($this->fixer->supports($file));
    }

    public function testConfigurationDefinition(): void
    {
        $configDefinition = $this->fixer->getConfigurationDefinition();
        $options = $configDefinition->getOptions();

        self::assertCount(3, $options);

        $optionNames = array_map(fn ($option) => $option->getName(), $options);
        self::assertContains('annotations', $optionNames);
        self::assertContains('preserve_existing', $optionNames);
        self::assertContains('separate', $optionNames);
    }

    public function testParseExistingAnnotations(): void
    {
        $method = new ReflectionMethod($this->fixer, 'parseExistingAnnotations');
        $method->setAccessible(true);

        $docBlock = "/**\n * @author John Doe\n * @license MIT\n */";
        $result = $method->invoke($this->fixer, $docBlock);

        $expected = [
            'author' => 'John Doe',
            'license' => 'MIT',
        ];

        self::assertSame($expected, $result);
    }

    public function testMergeAnnotations(): void
    {
        $method = new ReflectionMethod($this->fixer, 'mergeAnnotations');
        $method->setAccessible(true);

        $existing = ['author' => 'Existing Author', 'version' => '1.0'];
        $new = ['author' => 'New Author', 'license' => 'MIT'];

        $result = $method->invoke($this->fixer, $existing, $new);

        $expected = [
            'author' => 'New Author',
            'version' => '1.0',
            'license' => 'MIT',
        ];

        self::assertSame($expected, $result);
    }
}
