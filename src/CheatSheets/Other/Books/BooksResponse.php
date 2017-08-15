<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Response\AbstractResponse;

class BooksResponse extends AbstractResponse {
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
