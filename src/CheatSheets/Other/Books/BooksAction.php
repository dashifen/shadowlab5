<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;

class BooksAction extends AbstractAction {
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
			
			$searchbar = $this->container->get("searchbar");
			$searchbar = $this->constructSearchbar($searchbar);
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 *
	 * @return SearchbarInterface
	 */
	protected function constructSearchbar(SearchbarInterface $searchbar): SearchbarInterface {
		
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
		
		/** @var SearchbarInterface $searchbar */
		
		$searchbar->addRow();
		$searchbar->addSearch("Books", "book");
		$searchbar->addFilter("Included", "included", $options, "",
			"Both included and excluded");
		
		$searchbar->addReset();
		return $searchbar;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "book";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "books";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "books";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "book_id";
	}
	
}
