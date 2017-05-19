<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;

class BooksAction extends AbstractAction {
	public function execute(array $parameter = []): ResponseInterface {
		$this->processParameter($parameter);
		
		// our processParameter() method will set our action and recordId
		// properties when it can.  if it can't, then our action property
		// will be "read" by default.  we can call one of our methods below
		// using that property as follows; they'll handle the rest.
		
		return $this->{$this->action}();
	}
	
	protected function read() {
		$payload = $this->domain->read(["book_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$books = $payload->getDatum("books");
			
			
			$this->handleSuccess([
				"table"        => $books,
				"searchbar"    => $this->getSearchbar($payload),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"capabilities" => $this->request->getSessionVar("capabilities"),
				"caption"      => "The following are the SR books in this application.
					They're not all the published books for SR5; only those with
					quantifiable rules and stats are here.  Some, mostly the German
					content, are not included, i.e. you won't find their data
					elsewhere in the Shadowlab.",
			]);
		} else {
			$this->handleFailure([
				"noun"  => empty($parameter) ? "books" : "book",
				"title" => "Perception Failed",
			]);
		}
		
		return $this->response;
	}
	
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbar = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// if we're displaying more than one book, then we want a
			// searchbar to help the visitor find the one they're looking
			// for.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->newInstance('Shadowlab\Framework\AddOns\Searchbar');
			$searchbar = $searchbar->parse($payload->getDatum("books")["searchbar"]);
		}
		
		return $searchbar;
	}
}
