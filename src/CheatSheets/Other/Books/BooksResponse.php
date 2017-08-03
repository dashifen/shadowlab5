<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Response\ResponseException;
use Shadowlab\Framework\Response\AbstractResponse;

class BooksResponse extends AbstractResponse {
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$this->setResponseType("success");
		
		$template = $this->getTemplate($data, $action);
		
		$this->setContent($template);
		$this->setData($data);
	}
	
	public function handleFailure(array $data = [], string $action = "read"): void {
		$this->setResponseType("failure");
		$this->setContent($this->getTemplate($data, $action));
		$this->setData($data);
	}
	
	protected function getTemplate(array $data = [], string $action = "read"): string {
		switch ($this->responseType) {
			case "success":
				
				// the exact template we use for successful responses
				// changes based on what we're doing.  since this case
				// is more complex, we're going to handle it in its own
				// method below.
				
				return $this->getSuccessTemplate($data, $action);
			
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
			case "patch":
				return isset($data["count"]) && $data["count"] > 1
					? "collection.html"
					: "single.html";
			
			case "update":
				return "form.html";
		}
		
		return "record/not-found.html";
	}
}
