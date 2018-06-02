<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class BooksParser extends AbstractParser {
	/**
	 * @return void
	 * @throws MysqlException
	 */
	public function parse(): void {
		$xmlBooks = $this->getBooks();

		foreach ($xmlBooks as $book) {
			$this->db->upsert("books", $book, [
				"book"         => $book["book"],
				"abbreviation" => $book["abbreviation"],
			]);
		}
	}

	/**
	 * @return array
	 */
	protected function getBooks(): array {
		foreach ($this->xml->books->book as $book) {
			$books[] = [
				"book"         => (string) $book->name,
				"abbreviation" => (string) $book->code,
				"guid"         => strtoupper((string) $book->id),
			];
		}

		return $books ?? [];
	}
}

try {
	$parser = new BooksParser("data/books.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
