<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\Nodes;

use Generator;
use Latte\Compiler\Tag;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;

/**
 * {encore_js, entryName, ...}
 */
final class EncoreJsNode extends StatementNode
{
	public ExpressionNode $entryName;

	public ArrayNode $otherArguments;

	/**
	 * @throws \Latte\CompileException
	 */
	public static function create(Tag $tag): self
	{
		$tag->expectArguments();
		$node = new self;
		$node->entryName = $tag->parser->parseUnquotedStringOrExpression();

		$tag->parser->stream->tryConsume(',');
		$node->otherArguments = $tag->parser->parseArguments();

		return $node;
	}

	public function print(PrintContext $context): string
	{
		return $context->format(
			'echo $this->global->webpackEncoreTagRenderer->renderScriptTags(%node, %args) %line;',
			$this->entryName,
			$this->otherArguments,
			$this->position,
		);
	}

	public function &getIterator(): Generator
	{
		yield $this->entryName;
		yield $this->otherArguments;
	}
}
