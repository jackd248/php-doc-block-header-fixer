<div align="center">

# Php DocBlock Header Fixer

[![Coverage](https://img.shields.io/coverallsCoverage/github/jackd248/php-docblock-header-fixer?logo=coveralls)](https://coveralls.io/github/jackd248/php-docblock-header-fixer)
[![CGL](https://img.shields.io/github/actions/workflow/status/jackd248/php-docblock-header-fixer/cgl.yml?label=cgl&logo=github)](https://github.com/jackd248/php-docblock-header-fixer/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/jackd248/php-docblock-header-fixer/tests.yml?label=tests&logo=github)](https://github.com/jackd248/php-docblock-header-fixer/actions/workflows/tests.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/konradmichalik/php-docblock-header-fixer/php?logo=php)](https://packagist.org/packages/konradmichalik/php-docblock-header-fixer)

</div>

This packages contains a [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) rule to automatically fix the class header regarding PHP DocBlocks.

> [!warning]
> This package is in early development stage and may change significantly in the future. Use it at your own risk.

**Before:**

```php
<?php

class MyClass
{
    public function myMethod()
    {
        // ...
    }
}
```

**After:**

```php
<?php
/**
 * MyClass.
 *
 * @author Your Name <your@email.org>
 * @package MyPackage
 */
class MyClass
{
    // ...
}
```

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/konradmichalik/php-docblock-header-fixer?label=version&logo=packagist)](https://packagist.org/packages/konradmichalik/php-docblock-header-fixer)
[![Packagist Downloads](https://img.shields.io/packagist/dt/konradmichalik/php-docblock-header-fixer?color=brightgreen)](https://packagist.org/packages/konradmichalik/php-docblock-header-fixer)


```bash
composer require --dev konradmichalik/php-docblock-header-fixer
```

## ⚡ Usage

Add the PHP-CS-Fixer rule in your `.php-cs-fixer.php` file:

```php
<?php
// ...
return (new PhpCsFixer\Config())
    // ...
    ->registerCustomFixers([
        new KonradMichalik\PhpDocBlockHeaderFixer\Rules\DocBlockHeaderFixer()
    ])
    ->setRules([
        'KonradMichalik/docblock_header_comment' => [
            'annotations' => [
                'author' => 'Konrad Michalik <hej@konradmichalik.dev>',
                'license' => 'GPL-3.0-or-later',
                'package' => 'PhpDocBlockHeaderFixer',
            ],
            'preserve_existing' => true,
            'separate' => 'none',
        ],
    ])
;
```

Alternatively, you can use a object-oriented configuration:

```php
<?php
// ...
return (new PhpCsFixer\Config())
    // ...
    ->registerCustomFixers([
        new KonradMichalik\PhpDocBlockHeaderFixer\Rules\DocBlockHeaderFixer()
    ])
    ->setRules([
        KonradMichalik\PhpDocBlockHeaderFixer\Generators\DocBlockHeader::create(
            [
                'author' => 'Konrad Michalik <hej@konradmichalik.dev>',
                'license' => 'GPL-3.0-or-later',
                'package' => 'PhpDocBlockHeaderFixer',
            ],
            preserveExisting: true,
            separate: \KonradMichalik\PhpDocBlockHeaderFixer\Model\Separate::None
        )->__toArray()
    ])
;
```

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
