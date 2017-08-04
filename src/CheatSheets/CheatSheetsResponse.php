<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Response\ResponseException;
use Shadowlab\Framework\Response\ShadowlabResponse;

class CheatSheetsResponse extends ShadowlabResponse {
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$this->setContent("cheat-sheets/index.html");
		$this->setData($data);
	}
	
	// this response doesn't handle failure or errors well.  in fact,
	// it doesn't handle them at all!  if we couldn't find sheets to
	// display, then we'll send a 404 response using the method defined
	// by our parent.  so, for these, we'll throw a tantrum.
	
	public function handleFailure(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Failure",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleError(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Error",
			ResponseException::UNEXPECTED_RESPONSE);
	}
}
