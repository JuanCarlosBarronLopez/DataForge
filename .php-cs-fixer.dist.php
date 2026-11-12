<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/templates',
        __DIR__ . '/tests',
        __DIR__ . '/public',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PER-CS2.0' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'whitespace_after_comma_in_array' => true,
        'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_to_comment' => false,
        'blank_line_before_statement' => ['statements' => ['return']],
        'concat_space' => ['spacing' => 'none'],
    ])
    ->setFinder($finder);
