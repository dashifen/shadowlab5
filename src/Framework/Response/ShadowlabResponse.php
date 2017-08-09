<?php

namespace Shadowlab\Framework\Response;

use Dashifen\Response\ResponseException;

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
			"title"     => "Not Found",
			"heading"   => "Critical Glitch",
			"httpError" => "Not Found",
		]);
		
		$this->setContent($this->getTemplate($data, $action));
		$this->setData($data);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return string
	 * @throws ResponseException
	 */
	protected function getTemplate(array $data = [], string $action = "read"): string {
		
		// the purpose of this getTemplate() method is to look for HTTP errors.
		// if $data has a key for one, then we can do all the necessary work
		// to respond to our request here.
		
		if (!isset($data["httpError"])) {
			return "";
		}
		
		// we're going to play a little fast and loose with the single
		// responsibility principle.  technically, getting our template is
		// what we're supposed to be doing.  but, it'd also be nice if we
		// could set our statusCode here, too.
		
		$phrase = $data["httpError"];
		$statusCode = $this->getStatusCode($phrase);
		if ($statusCode === -1) {
			
			// if we don't have a valid phrase, then we'll just default to
			// a 406 (Bad Request) error.  it's probably not the best code,
			// but it's the closest thing we have to an unknown error that
			// is available to us.  plus, something probably was wrong with
			// the request or we wouldn't be here!
			
			$statusCode = 400;
		}
		
		$this->setStatusCode($statusCode);
		
		// and, here's where we tell the calling scope that we've found
		// our template.
		
		return "error.html";
	}
}
