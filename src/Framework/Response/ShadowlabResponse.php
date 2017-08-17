<?php

namespace Shadowlab\Framework\Response;

class ShadowlabResponse extends AbstractResponse {
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
