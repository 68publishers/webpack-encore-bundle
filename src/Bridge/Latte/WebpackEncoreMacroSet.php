<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use Latte\Helpers;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;

/**
 * Latte 2.x only
 */
final class WebpackEncoreMacroSet extends MacroSet
{
	public static function install(Compiler $compiler): void
	{
		$me = new self($compiler);

		$me->addMacro('encore_js', [$me, 'macroEncoreJs']);
		$me->addMacro('encore_css', [$me, 'macroEncoreCss']);
	}

	/**
	 * @throws \Latte\CompileException
	 */
	public function macroEncoreJs(MacroNode $node, PhpWriter $writer): string
	{
		$this->removeEscapeModifier($node);

		return $writer
			->using($node)
			->write('echo $this->global->webpackEncoreTagRenderer->renderScriptTags(%node.args)');
	}

	/**
	 * @throws \Latte\CompileException
	 */
	public function macroEncoreCss(MacroNode $node, PhpWriter $writer): string
	{
		$this->removeEscapeModifier($node);

		return $writer
			->using($node)
			->write('echo $this->global->webpackEncoreTagRenderer->renderLinkTags(%node.args)');
	}

	private function removeEscapeModifier(MacroNode $node): void
	{
		Helpers::removeFilter($node->modifiers, 'escape');
	}
}
