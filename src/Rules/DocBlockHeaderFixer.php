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
            'Add configurable DocBlock annotations before class, interface, trait, and enum declarations.',
            [],
        );
    }

    public function getName(): string
    {
        return 'KonradMichalik/docblock_header_comment';
    }

    public function getPriority(): int
    {
        // Run before single_line_after_imports (0) and no_extra_blank_lines (0)
        // Higher priority values run first
        return 1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_CLASS)
            || $tokens->isTokenKindFound(T_INTERFACE)
            || $tokens->isTokenKindFound(T_TRAIT)
            || $tokens->isTokenKindFound(T_ENUM);
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
                ->setDefault(Separate::None->value)
                ->getOption(),
            (new FixerOptionBuilder('add_structure_name', 'Add structure name before annotations'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('ensure_spacing', 'Ensure proper spacing after DocBlock to prevent conflicts with PHP-CS-Fixer rules'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
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

            if (!$token->isGivenKind([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                continue;
            }

            $structureName = $this->getStructureName($tokens, $index);
            $this->processStructureDocBlock($tokens, $index, $annotations, $structureName);
        }
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function processStructureDocBlock(Tokens $tokens, int $structureIndex, array $annotations, string $structureName): void
    {
        $existingDocBlockIndex = $this->findExistingDocBlock($tokens, $structureIndex);
        $preserveExisting = $this->resolvedConfiguration['preserve_existing'] ?? true;

        if (null !== $existingDocBlockIndex) {
            if ($preserveExisting) {
                $this->mergeWithExistingDocBlock($tokens, $existingDocBlockIndex, $annotations, $structureName);
            } else {
                $this->replaceDocBlock($tokens, $existingDocBlockIndex, $annotations, $structureName);
            }
        } else {
            $this->insertNewDocBlock($tokens, $structureIndex, $annotations, $structureName);
        }
    }

    private function getStructureName(Tokens $tokens, int $structureIndex): string
    {
        // Look for the structure name token after the keyword (class/interface/trait/enum)
        for ($i = $structureIndex + 1, $limit = $tokens->count(); $i < $limit; ++$i) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                continue;
            }

            // The first non-whitespace token after the keyword should be the structure name
            if ($token->isGivenKind(T_STRING)) {
                return $token->getContent();
            }

            // If we hit anything else, stop looking
            break;
        }

        return '';
    }

    private function findExistingDocBlock(Tokens $tokens, int $structureIndex): ?int
    {
        for ($i = $structureIndex - 1; $i >= 0; --$i) {
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
    private function mergeWithExistingDocBlock(Tokens $tokens, int $docBlockIndex, array $annotations, string $structureName): void
    {
        $existingContent = $tokens[$docBlockIndex]->getContent();
        $existingAnnotations = $this->parseExistingAnnotations($existingContent);
        $mergedAnnotations = $this->mergeAnnotations($existingAnnotations, $annotations);

        $newDocBlock = $this->buildDocBlock($mergedAnnotations, $structureName);
        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $newDocBlock]);

        // Ensure there's proper spacing after existing DocBlock
        $ensureSpacing = $this->resolvedConfiguration['ensure_spacing'] ?? true;
        if ($ensureSpacing) {
            $this->ensureProperSpacingAfterDocBlock($tokens, $docBlockIndex);
        }
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function replaceDocBlock(Tokens $tokens, int $docBlockIndex, array $annotations, string $structureName): void
    {
        $newDocBlock = $this->buildDocBlock($annotations, $structureName);
        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $newDocBlock]);

        // Ensure there's proper spacing after replaced DocBlock
        $ensureSpacing = $this->resolvedConfiguration['ensure_spacing'] ?? true;
        if ($ensureSpacing) {
            $this->ensureProperSpacingAfterDocBlock($tokens, $docBlockIndex);
        }
    }

    /**
     * @param array<string, string|array<string>> $annotations
     */
    private function insertNewDocBlock(Tokens $tokens, int $structureIndex, array $annotations, string $structureName): void
    {
        $separate = $this->resolvedConfiguration['separate'] ?? 'none';
        $insertIndex = $this->findInsertPosition($tokens, $structureIndex);

        $tokensToInsert = [];

        // Add separation before comment if needed
        if (in_array($separate, ['top', 'both'], true)) {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        // Add the DocBlock
        $docBlock = $this->buildDocBlock($annotations, $structureName);
        $tokensToInsert[] = new Token([T_DOC_COMMENT, $docBlock]);

        // Add a newline after the DocBlock if ensure_spacing is enabled (default)
        // This prevents conflicts with single_line_after_imports and no_extra_blank_lines rules
        $ensureSpacing = $this->resolvedConfiguration['ensure_spacing'] ?? true;
        if ($ensureSpacing) {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        // Add additional separation if configured
        if (in_array($separate, ['bottom', 'both'], true)) {
            // Check if there's already whitespace after the structure declaration
            $nextToken = $tokens[$structureIndex] ?? null;
            if (null !== $nextToken && !$nextToken->isWhitespace()) {
                $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
            }
        }

        $tokens->insertAt($insertIndex, $tokensToInsert);
    }

    private function findInsertPosition(Tokens $tokens, int $structureIndex): int
    {
        $insertIndex = $structureIndex;

        // Look backwards for attributes, final, abstract keywords
        for ($i = $structureIndex - 1; $i >= 0; --$i) {
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
    private function buildDocBlock(array $annotations, string $structureName): string
    {
        $addStructureName = $this->resolvedConfiguration['add_structure_name'] ?? false;

        if (empty($annotations) && !$addStructureName) {
            return "/**\n */";
        }

        $docBlock = "/**\n";

        // Add structure name with dot if configured
        if ($addStructureName && !empty($structureName)) {
            $docBlock .= " * {$structureName}.\n";

            // Add empty line after structure name if there are annotations - compatible with phpdoc_separation
            if (!empty($annotations)) {
                $docBlock .= " *\n";
            }
        }

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

    /**
     * Ensures proper spacing after a DocBlock to prevent conflicts with PHP-CS-Fixer rules.
     */
    private function ensureProperSpacingAfterDocBlock(Tokens $tokens, int $docBlockIndex): void
    {
        $nextIndex = $docBlockIndex + 1;

        // Check if the next token exists and is not already a newline
        if ($nextIndex < $tokens->count()) {
            $nextToken = $tokens[$nextIndex];

            // If the next token is not whitespace or doesn't contain a newline, add one
            if (!$nextToken->isWhitespace() || !str_contains($nextToken->getContent(), "\n")) {
                // Insert a newline token after the DocBlock
                $tokens->insertAt($nextIndex, [new Token([T_WHITESPACE, "\n"])]);
            }
        }
    }
}
