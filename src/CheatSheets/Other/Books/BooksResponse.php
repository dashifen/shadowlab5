<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Response\ResponseException;
use Shadowlab\Framework\Response\AbstractResponse;

class BooksResponse extends AbstractResponse {
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$this->setResponseType("success");
		
		// there's a variety of things that happen during a successful
		// response.  the method below will help us identify which template
		// to use for our content.
		
		$template = $this->getTemplate($data, $action);
		$this->setContent($template);
		$this->setData($data);
	}
	
	public function handleError(array $data = [], string $action = "read"): void {
		$this->setResponseType("error");
		
		// errors are usually handled by re-showing the form.  unless, in our
		// data, exists something that corresponds to an HTTP error phrase.
		// then we show a different template.
		
		$template = $this->getTemplate($data, $action);
		$this->setContent($template);
		$this->setData($data);
	}
	
	public function handleFailure(array $data = [], string $action = "read"): void {
		$this->setResponseType("failure");
		
		// like successful actions, failures come in a variety of ways.  so,
		// we use the method below to determine the template we use for our
		// content.
		
		$template = $this->getTemplate($data, $action);
		$this->setContent($template);
		$this->setData($data);
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
