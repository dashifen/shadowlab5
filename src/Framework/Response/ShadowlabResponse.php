<?php

namespace Shadowlab\Framework\Response;

/**
 * Class ShadowlabNotFoundResponse
 *
 * @package Shadowlab\Framework\Response
 */
class ShadowlabResponse extends Response {
	/**
	 * @param array $data
	 */
	public function handleNotFound(array $data = []): void {
		
		// in addition to whatever data the calling scope sent us,
		// we want to make sure to set the following so that they'll
		// be correct when the page loads.
		
		$data = array_merge($data, [
			"title"   => "Not Found",
			"heading" => "Critical Glitch",
		]);
		
		$this->setContent("not-found/route.html");
		$this->setData($data);
	}
}
