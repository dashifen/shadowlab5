<?php

namespace Shadowlab\Response;

use Dashifen\Response\AbstractResponse;

abstract class AbstractShadowlabResponse extends AbstractResponse {
	public function handleNotFound(array $data = []): void {
		$this->view->setContent("not-found.html");
		$this->setStatusCode(404);
	}
}
