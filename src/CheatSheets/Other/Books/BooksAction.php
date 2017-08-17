<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;

class BooksAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		
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
