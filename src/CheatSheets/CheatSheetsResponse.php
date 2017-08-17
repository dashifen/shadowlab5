<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Response\AbstractResponse;

class CheatSheetsResponse extends AbstractResponse {
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerTemplate(string $template): string {
		
		// we trust that our parent will handle the general template
		// identification correctly.  so this can just return the
		// $template that was sent here.
		
		return $template;
	}
	
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerSuccessTemplate(string $template): string {
		
		// when we successfully grab a set of sheets, then we want to
		// display them using the cheat-sheets index view.  our parent
		// can't know that, so we'll tell it that information here.
		
		return "cheat-sheets/index.html";
	}
	
}
