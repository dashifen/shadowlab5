<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Response\AbstractResponse;

class SpellsResponse extends AbstractResponse {
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerSuccessTemplate(string $template): string {
		return $template;
	}
	
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerTemplate(string $template): string {
		return $template;
	}
	
}
