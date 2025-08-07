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

namespace KonradMichalik\PhpDocBlockHeaderFixer\Rules;

use KonradMichalik\PhpDocBlockHeaderFixer\Enum\Separate;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @implements ConfigurableFixerInterface<array<string, mixed>, array<string, mixed>>
 */
final class DocBlockHeaderFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $resolvedConfiguration = [];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Add configurable DocBlock annotations before class declarations.',
            [],
        );
    }

    public function getName(): string
    {
        return 'KonradMichalik/docblock_header_comment';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('annotations', 'DocBlock annotations to add'))
                ->setAllowedTypes(['array'])
                ->setDefault([])
                ->getOption(),
            (new FixerOptionBuilder('preserve_existing', 'Preserve existing DocBlock annotations'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('separate', 'Separate the comment'))
                ->setAllowedValues(Separate::getList())
                ->setDefault(Separate::Both->value)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->resolvedConfiguration = $this->getConfigurationDefinition()->resolve($configuration ?? []);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $annotations = $this->resolvedConfiguration['annotations'] ?? [];
        if (empty($annotations)) {
            return;
        }

        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_CLASS)) {
                continue;
            }

            $this->processClassDocBlock($tokens, $index, $annotations);
        }
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function processClassDocBlock(Tokens $tokens, int $classIndex, array $annotations): void
    {
        $existingDocBlockIndex = $this->findExistingDocBlock($tokens, $classIndex);
        $preserveExisting = $this->resolvedConfiguration['preserve_existing'] ?? true;

        if (null !== $existingDocBlockIndex) {
            if ($preserveExisting) {
                $this->mergeWithExistingDocBlock($tokens, $existingDocBlockIndex, $annotations);
            } else {
                $this->replaceDocBlock($tokens, $existingDocBlockIndex, $annotations);
            }
        } else {
            $this->insertNewDocBlock($tokens, $classIndex, $annotations);
        }
    }

    private function findExistingDocBlock(Tokens $tokens, int $classIndex): ?int
    {
        for ($i = $classIndex - 1; $i >= 0; --$i) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                continue;
            }

            if ($token->isGivenKind(T_DOC_COMMENT)) {
                return $i;
            }

            // If we hit any other meaningful token (except modifiers), stop looking
            if (!$token->isGivenKind([T_FINAL, T_ABSTRACT, T_ATTRIBUTE])) {
                break;
            }
        }

        return null;
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function mergeWithExistingDocBlock(Tokens $tokens, int $docBlockIndex, array $annotations): void
    {
        $existingContent = $tokens[$docBlockIndex]->getContent();
        $existingAnnotations = $this->parseExistingAnnotations($existingContent);
        $mergedAnnotations = $this->mergeAnnotations($existingAnnotations, $annotations);

        $newDocBlock = $this->buildDocBlock($mergedAnnotations);
        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $newDocBlock]);
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function replaceDocBlock(Tokens $tokens, int $docBlockIndex, array $annotations): void
    {
        $newDocBlock = $this->buildDocBlock($annotations);
        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $newDocBlock]);
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function insertNewDocBlock(Tokens $tokens, int $classIndex, array $annotations): void
    {
        $separate = $this->resolvedConfiguration['separate'] ?? 'both';
        $insertIndex = $this->findInsertPosition($tokens, $classIndex);

        $tokensToInsert = [];

        // Add separation before comment if needed
        if (in_array($separate, ['top', 'both'], true)) {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        // Add the DocBlock
        $docBlock = $this->buildDocBlock($annotations);
        $tokensToInsert[] = new Token([T_DOC_COMMENT, $docBlock]);

        // Add separation after comment if needed
        if (in_array($separate, ['bottom', 'both'], true)) {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        $tokens->insertAt($insertIndex, $tokensToInsert);
    }

    private function findInsertPosition(Tokens $tokens, int $classIndex): int
    {
        $insertIndex = $classIndex;

        // Look backwards for attributes, final, abstract keywords
        for ($i = $classIndex - 1; $i >= 0; --$i) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                continue;
            }

            if ($token->isGivenKind([T_FINAL, T_ABSTRACT, T_ATTRIBUTE])) {
                $insertIndex = $i;
                continue;
            }

            break;
        }

        return $insertIndex;
    }

    /**
     * @return array<string, string>
     */
    private function parseExistingAnnotations(string $docBlockContent): array
    {
        $lines = explode("\n", $docBlockContent);
        $annotations = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t\r\n/*");
            if (preg_match('/^@(\w+)(?:\s+(.*))?$/', $line, $matches)) {
                $tag = $matches[1];
                $value = $matches[2] ?? '';
                $annotations[$tag] = $value;
            }
        }

        return $annotations;
    }

    /**
     * @param array<string, string>               $existing
     * @param array<string, string|array<string>> $new
     *
     * @return array<string, string|array<string>>
     */
    private function mergeAnnotations(array $existing, array $new): array
    {
        // New annotations take precedence, but we keep existing ones that aren't being overridden
        return array_merge($existing, $new);
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function buildDocBlock(array $annotations): string
    {
        if (empty($annotations)) {
            return "/**\n */";
        }

        $docBlock = "/**\n";

        foreach ($annotations as $tag => $value) {
            if (empty($value)) {
                $docBlock .= " * @{$tag}\n";
            } elseif (is_array($value)) {
                // Handle multiple values for the same tag (e.g., multiple authors)
                foreach ($value as $singleValue) {
                    $docBlock .= " * @{$tag} {$singleValue}\n";
                }
            } else {
                $docBlock .= " * @{$tag} {$value}\n";
            }
        }

        $docBlock .= ' */';

        return $docBlock;
    }
}
