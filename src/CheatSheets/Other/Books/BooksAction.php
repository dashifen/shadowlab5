<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\Elements\Search;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar;

class BooksAction extends AbstractAction {
	/**
	 * @param array $parameter
	 *
	 * @return ResponseInterface
	 */
	public function execute(array $parameter = []): ResponseInterface {
		$this->processParameter($parameter);
		
		// our processParameter() method will set our action and recordId
		// properties when it can.  if it can't, then our action property
		// will be "read" by default.  we can call one of our methods below
		// using that property as follows; they'll handle the rest.
		
		return $this->{$this->action}();
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function read() {
		$payload = $this->domain->read(["book_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"table"        => $payload->getDatum("books"),
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
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbarHTML = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// if we're displaying more than one book, then we want a
			// searchbar to help the visitor find the one they're looking
			// for.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->newInstance('Shadowlab\Framework\AddOns\Searchbar');
			$searchbar = $this->constructSearchbar($searchbar, $payload);
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function constructSearchbar(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		
		// our searchbar should offer a way to search within a book's
		// name as well as a way to filter by those books which are or
		// are not included in the rest of the Shadowlab app.  luckily,
		// the construction of this bar doesn't require sifting through
		// our data since we know the only values for our included
		// column are "included" and "excluded."
		
		$options = [
			"included" => "Included",
			"excluded" => "Excluded",
		];
		
		/** @var Searchbar $searchbar */
		
		$searchbar->addRow();
		$searchbar->addSearch("Books", "book");
		$searchbar->addFilter("Included", "included", $options, "",
			"Both included and excluded");
		
		$searchbar->addReset();
		return $searchbar;
	}
	
	protected function update() {
		
		// if we're getting data to update, then this is actually
		// a special sort of read from the database.  the domain can
		// tell the difference between looking up old data and patching
		// new data because our parameter here doesn't include any new
		// data.
		
		$payload = $this->domain->update(["book_id" => $this->recordId]);
		
		// now, our $payload can tell us how to build a form for this
		// information on the client side similar to how we build a
		// searchbar when reading multiple books above.
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([]);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
	}
	
	protected function patch() {
		
		// when patching data, our $_POST is full of shiny new stuff
		// for our database to process.  we'll pass it along to our
		// domain where the validator and other objects within it can
		// work their magic.
		
		$payload = $this->domain->update([
			"posted" => $this->request->getPost(),
		]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([]);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
	}
}
