<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;

class BooksAction extends AbstractAction {
	public function execute(string $parameter = ""): ResponseInterface {
		
		// if our $parameter is not empty, then we're loading a specific
		// book rather than all of the books.  luckily, the domain will
		// know how to limit the SQL statement it uses based on the state
		// of what we send it.  we expect the parameter to be a book's
		// abbreviation (e.g. SR5) so that's how we name it for our domain.
		// note: the abbreviations in the database are in all caps, so
		// we'll be sure to send that information here.
		
		$payload = $this->domain->read(["abbr" => strtoupper($parameter)]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"title" => $payload->getDatum("title"),
				"table" => $payload->getDatum("books"),
				"count" => $payload->getDatum("count"),
			]);
		} else {
			$this->handleFailure([
				"noun"  => empty($parameter) ? "books" : "book",
				"title" => "Perception Failed",
			]);
		}
		
		return $this->response;
	}
}
