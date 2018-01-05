<?php

/**
 * MN Break and Continue
 *
 * @author    Marion Newlevant
 * @copyright Copyright (c) 2014, Marion Newlevant
 * @license   MIT
 * @link      https://github.com/marionnewlevant/craft-mnbreakandcontinue
 */

require_once('Continue_Node.php');

class Continue_TokenParser extends \Twig_TokenParser
{

	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Continue_Node(array(), array(), $lineno, $this->getTag());
	}

	public function getTag()
	{
		return 'continue';
	}

}

