<?php

/**
 * MN Break and Continue
 *
 * @author    Marion Newlevant
 * @copyright Copyright (c) 2014, Marion Newlevant
 * @license   MIT
 * @link      https://github.com/marionnewlevant/craft-mnbreakandcontinue
 */

require_once('Break_TokenParser.php');
require_once('Continue_TokenParser.php');

class MNBreakAndContinueTwigExtension extends \Twig_Extension
{
	public function getTokenParsers()
	{
		return array(
			new Break_TokenParser(),
			new Continue_TokenParser(),
		);
	}

	public function getName()
	{
		return 'MN Break and Continue';
	}
}
