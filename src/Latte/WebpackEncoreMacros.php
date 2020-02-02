<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Latte;

use Latte;

final class WebpackEncoreMacros extends Latte\Macros\MacroSet
{
	/**
	 * @param string          $jsAssetsMacroName
	 * @param string          $cssAssetsMacroName
	 * @param \Latte\Compiler $compiler
	 *
	 * @return void
	 */
	public static function install(string $jsAssetsMacroName, string $cssAssetsMacroName, Latte\Compiler $compiler): void
	{
		$me = new static($compiler);

		$me->addMacro($jsAssetsMacroName, [$me, 'macroJsAssets']);
		$me->addMacro($cssAssetsMacroName, [$me, 'macroCssAssets']);
	}

	/**
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 *
	 * @return string
	 * @throws \Latte\CompileException
	 */
	public function macroJsAssets(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		$this->removeEscapeModifier($node);

		return $writer
			->using($node)
			->write('echo %modify($this->global->webpackEncoreTagRenderer->renderJsTags(%node.args))');
	}

	/**
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 *
	 * @return string
	 * @throws \Latte\CompileException
	 */
	public function macroCssAssets(Latte\MacroNode $node, Latte\PhpWriter $writer): string
	{
		$this->removeEscapeModifier($node);

		return $writer
			->using($node)
			->write('echo %modify($this->global->webpackEncoreTagRenderer->renderCssTags(%node.args))');
	}

	/**
	 * @param \Latte\MacroNode $node
	 */
	private function removeEscapeModifier(Latte\MacroNode $node): void
	{
		/** @noinspection PhpInternalEntityUsedInspection */
		Latte\Helpers::removeFilter($node->modifiers, 'escape');
	}
}
