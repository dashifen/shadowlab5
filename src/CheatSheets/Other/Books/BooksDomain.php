<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Domain\AbstractDomain;

class BooksDomain extends AbstractDomain {
	/**
	 * @param bool $view
	 *
	 * @return array [string, string, string]
	 */
	protected function getRecordDetails($view = false): array {
		return ["book_id", "books", "book"];
	}
	
	/**
	 * @param int $book_id
	 *
	 * @return array
	 */
	protected function readOne(int $book_id): array {
		$sql = $this->getQuery() . " WHERE book_id = :book_id
			AND deleted = 0";
		
		return $this->db->getRow($sql, ["book_id" => $book_id]);
	}
	
	/**
	 * @return string
	 */
	protected function getQuery(): string {
		return "SELECT book_id, book, description, abbreviation,
			included FROM books";
	}
	
	/**
	 * @return array
	 */
	protected function readAll(): array {
		$sql = $this->getQuery() . " WHERE deleted = 0 ORDER BY book";
		return $this->db->getResults($sql);
	}
	
	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return !$isCollection ? $records[0]["book"] : "Books";
	}
}
