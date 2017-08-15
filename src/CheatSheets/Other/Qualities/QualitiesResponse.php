<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Response\AbstractResponse;

class QualitiesResponse extends AbstractResponse {
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
