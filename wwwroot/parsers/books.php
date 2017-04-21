<?php
require("../vendor/autoload.php");

use League\BooBoo\Runner;
use League\BooBoo\Formatter\HtmlTableFormatter;
use Shadowlab\Database\ShadowlabDatabase;
use Dashifen\Database\DatabaseException;

$runner = new Runner();
$runner->pushFormatter(new HtmlTableFormatter());
$runner->register();

$db  = new ShadowlabDatabase();
$xml = new SimpleXMLElement(file_get_contents("data/books.xml"));

$books = [];
foreach ($xml->books->book as $book) {
	$books[] = [
		"book" => (string) $book->name,
		"abbr" => (string) $book->code,
	];
}

try {
	if (sizeof($books) > 0) {
		foreach ($books as $book) {
			$db->upsert("books", $book, ["book" => $book["book"]]);
		}
	}
	
	echo "done";
} catch (DatabaseException $e) {
	echo "Failed: " . $e->getQuery() . "<pre>" . print_r($e, true) . "</pre>";
}
