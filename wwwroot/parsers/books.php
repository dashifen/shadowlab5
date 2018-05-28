<?php
require("../../vendor/autoload.php");

use Shadowlab\Framework\Database\Database;
use Dashifen\Database\DatabaseException;

function debug(...$x) {
	$dumps = [];
	foreach ($x as $y) {
		$dumps[] = print_r($y, true);
	}
	
	echo "<pre>" . join("</pre><pre>", $dumps) . "</pre>";
}

$db  = new Database();
$xml = new SimpleXMLElement(file_get_contents("data/books.xml"));

$books = [];
foreach ($xml->books->book as $book) {
	$books[] = [
		"book"         => (string) $book->name,
		"abbreviation" => (string) $book->code,
		"guid"         => strtoupper((string) $book->id),
	];
}

$db_books = $db->getCol("SELECT book FROM books");
$new_books = array_diff(array_column($books, "book"), $db_books);

try {
	if (sizeof($books) > 0) {
		foreach ($books as $book) {
			$db->upsert("books", $book, [
				"book"         => $book["book"],
				"abbreviation" => $book["abbreviation"]
			]);
		}
	}
	
	debug($new_books);
	echo "done";
} catch (DatabaseException $e) {
	echo "Failed: " . $e->getQuery() . "<pre>" . print_r($e, true) . "</pre>";
}
