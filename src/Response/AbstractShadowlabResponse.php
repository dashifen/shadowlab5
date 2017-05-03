<?php

namespace Shadowlab\Response;

use Dashifen\Response\AbstractResponse;

/**
 * Class AbstractShadowlabResponse
 *
 * implements the handleNotFound() method of the AbstractResponse class
 * so that specific responses don't have to.
 *
 * @package Shadowlab\Response
 */
abstract class AbstractShadowlabResponse extends AbstractResponse {
	/**
	 * @param array $data
	 */
	public function handleNotFound(array $data = []): void {
		$this->view->setContent("not-found.html");
		$this->setStatusCode(404);
	}
}
