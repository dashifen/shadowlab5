<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Domain\AbstractTransformer;

class BooksTransformer extends AbstractTransformer {
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function extractRecordCells(array $record): array {
		
		// for most Domains, removing columns like the book's name and
		// abbreviation is great.  but, for our list of books, it's less
		// great.  so, we'll just make sure that doesn't happen here:
		
		return ["book", "abbreviation"];
	}
	
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {
		return $header === "abbreviation" ? "ABBR" : "";
	}
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderClasses(string $header, array $records): string {
		$classes = "";
		
		switch ($header) {
			case "abbreviation":
				$classes = "w10";
				break;
			
			case "included":
				$classes = "icon text-center";
				break;
		}
		
		return $classes;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getSearchbarValue(string $column, string $value, array $record): string {
		$sbValue = "";
		
		switch ($column) {
			case "book":
				$sbValue = strip_tags($value);
				break;
			
			case "included":
				$sbValue = $value === "Y" ? "included" : "excluded";
				break;
		}
		
		return $sbValue;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {
		return $column === "book"
			? sprintf('<a href="#">%s</a>', $value)
			: $value;
	}
}
