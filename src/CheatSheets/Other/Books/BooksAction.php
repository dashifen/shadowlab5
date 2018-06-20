<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;

class BooksAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		$searchbar->addSearch("Books", "book");
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
