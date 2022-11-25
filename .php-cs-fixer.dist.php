<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/tests')
	->name(['*.php', '*.phpt']);

return (new PhpCsFixer\Config())
	->setUsingCache(FALSE)
	->setIndent("\t")
	->setRules([
		'@PSR2' => TRUE,
		'array_syntax' => ['syntax' => 'short'],
		'trailing_comma_in_multiline' => TRUE,
		'constant_case' => [
			'case' => 'upper',
		],
		'declare_strict_types' => TRUE,
		'phpdoc_align' => TRUE,
		'blank_line_after_opening_tag' => TRUE,
		'blank_line_before_statement' => [
			'statements' => ['break', 'continue', 'declare', 'return'],
		],
		'blank_line_after_namespace' => TRUE,
		'single_blank_line_before_namespace' => TRUE,
		'return_type_declaration' => [
			'space_before' => 'none',
		],
		'ordered_imports' => [
			'sort_algorithm' => 'length',
			'imports_order' => ['class', 'function', 'const'],
		],
		'no_unused_imports' => TRUE,
		'single_line_after_imports' => TRUE,
		'no_leading_import_slash' => TRUE,
		'global_namespace_import' => [
			'import_constants' => TRUE,
			'import_functions' => TRUE,
			'import_classes' => TRUE,
		],
		'concat_space' => [
			'spacing' => 'one',
		],
	])
	->setRiskyAllowed(TRUE)
	->setFinder($finder);
