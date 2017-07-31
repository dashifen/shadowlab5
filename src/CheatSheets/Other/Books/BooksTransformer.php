<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\Transformer;

class BooksTransformer extends Transformer {
	
	/*
	 * READ TRANSFORMATIONS
	 */
	
	public function transformRead(PayloadInterface $payload): PayloadInterface {
		
		// our payload should contain a book or books.  if it's the latter,
		// then the count will be greater then one
		
		$original = $payload->getDatum("books");
		$transformed = $payload->getDatum("count") > 1
			? $this->transformAll($original)
			: $this->transformOne($original);
		
		$payload->setDatum("books", $transformed);
		return $payload;
	}
	
	protected function transformAll(array $books): array {
		
		// our collection view expects a data structure that defines the
		// table that it displays.  so we need to make a header and body
		// out of our array of book information.
		
		$headers = $this->constructHeaders($books);
		$bodies = $this->constructBodies($books);
		
		return [
			"headers"   => $headers,
			"bodies"    => $bodies,
		];
	}
	
	protected function constructHeaders(array $books): array {
		$headers = [];
		
		// our parent object has a function that identifies the headers
		// we want to work with here.  that'll remove the book_id and
		// description column from our working set of columns.  the only
		// data we want removed from our headers, in this case, is the
		// description, so we'll send only that to our parent's method.
		
		$columns = $this->extractHeaders($books, ["description"]);
		
		foreach ($columns as $column) {
			$abbreviation = "";
			$display = $column;
			$classes = "";
			
			switch ($column) {
				case "abbr":
					$abbreviation = strtoupper($column);
					$display = "Abbreviation";
					$classes = "w10";
					break;
				
				case "included":
					$classes = "icon text-center";
					break;
			}
			
			$headers[] = [
				"id"           => $this->sanitize($column),
				"classes"      => $classes,
				"abbreviation" => $abbreviation,
				"display"      => $display,
			];
		}
		
		return $headers;
	}
	
	protected function constructBodies(array $books): array {
		$bodies = [];
		
		// and now, we'll build our table's body.  this is a little harder
		// since it's made up of multiple <tbody> tags each with two rows.
		// the first is our data row; the second is our description.  so,
		// each individual $book in our array needs to be split into these
		// two rows.  luckily, our parent can handle a lot of that for us.
		
		foreach ($books as $book) {
			
			// we very carefully arrange our queries such that the first
			// element of our records is always the ID number in the database
			// for the record.  that allows us to array_shift here removing
			// that datum from the array and allows our parent to handle the
			// rest of it.
			
			$temp["recordId"] = array_shift($book);
			$temp["description"] = $this->extractDescription($book);
			$temp["data"] = $this->extractData($book);
			$bodies[] = $temp;
		}
		
		return $bodies;
	}
	
	protected function extractDescription(array $record, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// the default behavior of our parent's method is to include the abbr
		// in the description for the page reference.  but, in this case, the
		// abbr is data so we need to alter the array of descriptive keys that
		// our parent works with.
		
		return parent::extractDescription($record, ["description"]);
	}
	
	protected function extractData(array $spell, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// like above, the parent would normally remove the abbr from our data
		// in favor of keeping it as a part of the description.  we'll want to
		// send the same list of descriptive keys here as we did above to avoid
		// this.
		
		$data = parent::extractData($spell, ["description"]);
		
		// now that we have our data, there's one thing we need to do to it.
		// the included column should display Y and N (as an answer to the
		// question: is this book included?), but we want the searchbar to
		// use "included" and "excluded" as its matching terms.  while the
		// rest of our searchbar work happens below, since this is about the
		// way our table's body is constructed, we'll do this work here.
		
		foreach ($data as &$datum) {
			switch ($datum["column"]) {
				case "book":
					$datum["searchbarValue"] = strip_tags($datum["html"]);
					$datum["html"] = sprintf('<a href="#">%s</a>', $datum["html"]);
					break;
				
				case "included":
					$datum["searchbarValue"] = $datum["html"] === "Y"
						? "included"
						: "excluded";
			}
		}
		
		return $data;
	}
	
	protected function transformOne(array $books): array {
		return $books;
	}
	
	/*
	 * UPDATE TRANSFORMATIONS
	 */

	public function transformUpdate(PayloadInterface $payload): PayloadInterface {
		
		// no transformation is necessary when patching the database, so
		// when we're here, it's after we've read information about a book
		// from the database.  therefore, what we want to do is transform
		// our data so that our client can construct our form.
		
		$book = $payload->getDatum("books");
		$schema = $payload->getDatum("schema");
		
		die("<pre>" . print_r($payload, true) . "</pre>");
		
	}
	
}
