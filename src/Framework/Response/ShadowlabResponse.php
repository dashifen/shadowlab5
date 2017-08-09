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
	 *
	 * @return void
	 */
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$this->setResponseType("success");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	protected function setTemplate(array $data = [], string $action = "read"): void {
		
		// by default, to set our template, we use our $data and $action
		// parameters to identify it.  then, we can set it as this response's
		// content, and specify the data that goes into it.
		
		$template = $this->getTemplate($data, $action);
		$this->setContent($template);
		$this->setData($data);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleFailure(array $data = [], string $action = "read"): void {
		$this->setResponseType("failure");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleError(array $data = [], string $action = "read"): void {
		$this->setResponseType("error");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
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
		
		$this->setResponseType("notfound");
		$this->setTemplate($data, $action);
	}
	
	protected function getTemplate(array $data = [], string $action = "read"): string {
		
		// if our parent can identify a template, then we use it by default.
		// this is most commonly the case for HTTP errors.  otherwise, we'll
		// continue with the switch statement below to handle responses
		// unique to this handler.
		
		if (!empty($template = parent::getTemplate($data, $action))) {
			return $template;
		}
		
		switch ($this->responseType) {
			case "success":
				
				// the exact template we use for successful responses
				// changes based on what we're doing.  since this case
				// is more complex, we're going to handle it in its own
				// method below.
				
				return $this->getSuccessTemplate($data, $action);
			
			case "error":
				return "update/form.html";
			
			case "failure":
			case "notfound":
				return "not-found/record.php";
				
			default:
				if (empty($this->responseType)) {
					throw new ResponseException("Response Type Required");
				}
		}
		
		return "";
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return string
	 */
	protected function getSuccessTemplate(array $data = [], string $action = "read"): string {
		
		switch ($action) {
			case "read":
				
				// when we're reading information, the template we use is
				// based on the number of items we've selected from the
				// database.  more than one and we should a collection of
				// items; otherwise, a single one.
				
				return isset($data["count"]) && $data["count"] > 1
					? "read/collection.html"
					: "read/single.html";
			
			case "update":
				
				// when updating, if we have a record of our success, then
				// we'll want to share that success with our visitor.
				// otherwise, we give them the form so they can enter data
				// we use to perform our update.
				
				return isset($data["success"]) && $data["success"]
					? "update/success.html"
					: "update/form.html";
		}
		
		return "not-found/record.html";
	}
}
