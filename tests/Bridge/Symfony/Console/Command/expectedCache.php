<?php

declare(strict_types=1);

// This file has been auto-generated by the Symfony Cache Component.

return [[

'_default' => 0,
'other_build' => 1,

], [

0 => [
	'entrypoints' => [
		'my_entry' => [
			'js' => [
				'file1.js',
				'file2.js',
			],
			'css' => [
				'styles.css',
				'styles2.css',
			],
		],
		'other_entry' => [
			'js' => [
				'file1.js',
				'file3.js',
			],
			'css' => [],
		],
	],
	'integrity' => [
		'file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
		'styles.css' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
	],
],
1 => [
	'entrypoints' => [
		'other_entry' => [
			'js' => [
				'file1.js',
				'file2.js',
			],
			'css' => [
				'styles.css',
			],
		],
	],
],

]];
