<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - PHP-CS-Fixer Configuration
 *
 * This file defines the coding standards rules for the bundle.
 * Run with: vendor/bin/php-cs-fixer fix
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'Resources/public',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP81Migration' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],

        // Imports
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],

        // Spaces and formatting
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],

        // PHPDoc
        'phpdoc_align' => [
            'align' => 'vertical',
        ],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var', 'psalm-suppress'],
        ],

        // Class definition
        'class_definition' => [
            'single_line' => true,
        ],
        'single_class_element_per_statement' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        // Strict types
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        // Type declarations
        'void_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,

        // Comments
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'multiline_comment_opening_closing' => true,

        // Misc
        'yoda_style' => false,
        'native_function_invocation' => false,
        // Disable modern_serialization_methods as we need __sleep for Pimcore compatibility
        // __sleep returns array of property names (Pimcore pattern)
        // __serialize returns array of values (different behavior)
        'modern_serialization_methods' => false,
    ])
    ->setFinder($finder);
