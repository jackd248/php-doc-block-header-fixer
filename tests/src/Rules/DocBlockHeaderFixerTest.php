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

        $expected = "/**\n * @author John Doe <john@example.com>\n */";
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

        $expected = "/**\n * @author John Doe <john@example.com>\n * @license MIT\n * @package MyPackage\n */";
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

        $expected = "/**\n * @author John Doe <john@example.com>\n * @author Jane Smith <jane@example.com>\n * @license MIT\n */";
        self::assertSame($expected, $result);
    }

    public function testBuildDocBlockWithEmptyValue(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, [
            'deprecated' => '',
            'author' => 'John Doe',
        ]);

        $expected = "/**\n * @deprecated\n * @author John Doe\n */";
        self::assertSame($expected, $result);
    }

    public function testBuildDocBlockWithNullValue(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, [
            'internal' => null,
            'license' => 'MIT',
        ]);

        $expected = "/**\n * @internal\n * @license MIT\n */";
        self::assertSame($expected, $result);
    }

    public function testBuildDocBlockWithMixedEmptyAndNonEmptyValues(): void
    {
        $method = new ReflectionMethod($this->fixer, 'buildDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, [
            'deprecated' => '',
            'author' => 'John Doe',
            'internal' => null,
            'license' => 'MIT',
            'api' => '',
        ]);

        $expected = "/**\n * @deprecated\n * @author John Doe\n * @internal\n * @license MIT\n * @api\n */";
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

    public function testApplyFixWithEmptyAnnotations(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $file = new SplFileInfo(__FILE__);

        $method = new ReflectionMethod($this->fixer, 'applyFix');
        $method->setAccessible(true);

        $this->fixer->configure(['annotations' => []]);
        $method->invoke($this->fixer, $file, $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    public function testApplyFixAddsDocBlockToClass(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $file = new SplFileInfo(__FILE__);

        $method = new ReflectionMethod($this->fixer, 'applyFix');
        $method->setAccessible(true);

        $this->fixer->configure([
            'annotations' => ['author' => 'John Doe'],
            'separate' => 'none',
        ]);
        $method->invoke($this->fixer, $file, $tokens);

        $expected = "<?php /**\n * @author John Doe\n */class Foo {}";
        self::assertSame($expected, $tokens->generateCode());
    }

    public function testApplyFixHandlesMultipleClasses(): void
    {
        $code = '<?php class Foo {} class Bar {}';
        $tokens = Tokens::fromCode($code);
        $file = new SplFileInfo(__FILE__);

        $method = new ReflectionMethod($this->fixer, 'applyFix');
        $method->setAccessible(true);

        $this->fixer->configure([
            'annotations' => ['author' => 'John Doe'],
            'separate' => 'none',
        ]);
        $method->invoke($this->fixer, $file, $tokens);

        $expected = "<?php /**\n * @author John Doe\n */class Foo {} /**\n * @author John Doe\n */class Bar {}";
        self::assertSame($expected, $tokens->generateCode());
    }

    public function testProcessClassDocBlockWithNewDocBlock(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'processClassDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['separate' => 'none']);
        $method->invoke($this->fixer, $tokens, 1, $annotations);

        $expected = "<?php /**\n * @author John Doe\n */class Foo {}";
        self::assertSame($expected, $tokens->generateCode());
    }

    public function testProcessClassDocBlockWithExistingDocBlockPreserve(): void
    {
        $code = "<?php /**\n * @license MIT\n */ class Foo {}";
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'processClassDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['preserve_existing' => true]);
        $method->invoke($this->fixer, $tokens, 2, $annotations);

        self::assertStringContainsString('@license MIT', $tokens->generateCode());
        self::assertStringContainsString('@author John Doe', $tokens->generateCode());
    }

    public function testProcessClassDocBlockWithExistingDocBlockReplace(): void
    {
        $code = "<?php /**\n * @license MIT\n */ class Foo {}";
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'processClassDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['preserve_existing' => false]);
        $method->invoke($this->fixer, $tokens, 2, $annotations);

        self::assertStringNotContainsString('@license MIT', $tokens->generateCode());
        self::assertStringContainsString('@author John Doe', $tokens->generateCode());
    }

    public function testFindExistingDocBlockFound(): void
    {
        $code = "<?php /**\n * @author John Doe\n */ class Foo {}";
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findExistingDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 2);

        self::assertSame(1, $result);
    }

    public function testFindExistingDocBlockNotFound(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findExistingDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 1);

        self::assertNull($result);
    }

    public function testFindExistingDocBlockWithModifiers(): void
    {
        $code = "<?php /**\n * @author John Doe\n */ final class Foo {}";
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findExistingDocBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 3);

        self::assertSame(1, $result);
    }

    public function testMergeWithExistingDocBlock(): void
    {
        $code = "<?php /**\n * @license MIT\n */ class Foo {}";
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'mergeWithExistingDocBlock');
        $method->setAccessible(true);

        $method->invoke($this->fixer, $tokens, 1, $annotations);

        self::assertStringContainsString('@license MIT', $tokens->generateCode());
        self::assertStringContainsString('@author John Doe', $tokens->generateCode());
    }

    public function testReplaceDocBlock(): void
    {
        $code = "<?php /**\n * @license MIT\n */ class Foo {}";
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'replaceDocBlock');
        $method->setAccessible(true);

        $method->invoke($this->fixer, $tokens, 1, $annotations);

        self::assertStringNotContainsString('@license MIT', $tokens->generateCode());
        self::assertStringContainsString('@author John Doe', $tokens->generateCode());
    }

    public function testInsertNewDocBlockWithSeparateNone(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'insertNewDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['separate' => 'none']);
        $method->invoke($this->fixer, $tokens, 1, $annotations);

        $expected = "<?php /**\n * @author John Doe\n */class Foo {}";
        self::assertSame($expected, $tokens->generateCode());
    }

    public function testInsertNewDocBlockWithSeparateTop(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'insertNewDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['separate' => 'top']);
        $method->invoke($this->fixer, $tokens, 1, $annotations);

        $result = $tokens->generateCode();
        self::assertStringContainsString('@author John Doe', $result);
        self::assertGreaterThanOrEqual(3, substr_count($result, "\n"));
    }

    public function testInsertNewDocBlockWithSeparateBottom(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'insertNewDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['separate' => 'bottom']);
        $method->invoke($this->fixer, $tokens, 1, $annotations);

        $result = $tokens->generateCode();
        self::assertStringContainsString('@author John Doe', $result);
        self::assertGreaterThanOrEqual(3, substr_count($result, "\n"));
    }

    public function testInsertNewDocBlockWithSeparateBoth(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);
        $annotations = ['author' => 'John Doe'];

        $method = new ReflectionMethod($this->fixer, 'insertNewDocBlock');
        $method->setAccessible(true);

        $this->fixer->configure(['separate' => 'both']);
        $method->invoke($this->fixer, $tokens, 1, $annotations);

        $result = $tokens->generateCode();
        self::assertStringContainsString('@author John Doe', $result);
        self::assertGreaterThanOrEqual(4, substr_count($result, "\n"));
    }

    public function testFindInsertPositionSimpleClass(): void
    {
        $code = '<?php class Foo {}';
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findInsertPosition');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 1);

        self::assertSame(1, $result);
    }

    public function testFindInsertPositionWithFinalModifier(): void
    {
        $code = '<?php final class Foo {}';
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findInsertPosition');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 2);

        self::assertSame(1, $result);
    }

    public function testFindInsertPositionWithAbstractModifier(): void
    {
        $code = '<?php abstract class Foo {}';
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findInsertPosition');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 2);

        self::assertSame(1, $result);
    }

    public function testFindInsertPositionWithAttribute(): void
    {
        $code = '<?php #[SomeAttribute] class Foo {}';
        $tokens = Tokens::fromCode($code);

        $method = new ReflectionMethod($this->fixer, 'findInsertPosition');
        $method->setAccessible(true);

        $result = $method->invoke($this->fixer, $tokens, 2);

        self::assertSame(1, $result);
    }
}
