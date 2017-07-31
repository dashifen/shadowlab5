<?php

namespace Shadowlab\Framework\Response;

/**
 * Class ShadowlabNotFoundResponse
 *
 * @package Shadowlab\Framework\Response
 */
class ShadowlabResponse extends AbstractResponse {
	/**
	 * @param array  $data
	 * @param string $action
	 */
	public function handleNotFound(array $data = [], string $action = "read"): void {
		
		// in addition to whatever data the calling scope sent us,
		// we want to make sure to set the following so that they'll
		// be correct when the page loads.
		
		$data = array_merge($data, [
			"title"   => "Not Found",
			"heading" => "Critical Glitch",
		]);
		
		$this->setContent($this->getTemplate($data, $action));
		$this->setData($data);
	}
	
	protected function getTemplate(array $data = [], string $action = "read"): string {
		return "not-found/route.html";
	}
}
