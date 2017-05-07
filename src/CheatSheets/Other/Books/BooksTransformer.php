<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\Transformer;

class BooksTransformer extends Transformer {
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
		// out of our array of book information.  we know we have at least
		// one book to transform or we wouldn't be here, so for our header
		// we can focus on that book and begin our work.
		
		$header = [];
		foreach (array_keys($books[0]) as $column) {
			$classes = "";
			
			if ($column === "book_id" || $column === "description") {
				continue;
			}
			
			switch ($column) {
				case "abbr":
					$classes = "w10";
					break;
				
				case "included":
					$classes = "icon text-center filtered";
					break;
			}
			
			$header[] = [
				"id"      => $this->sanitizeId($column),
				"classes" => $classes,
				"display" => $column,
			];
		}
		
		// and now, we'll build our table's body.  this is a little harder
		// since it's made up of multiple <tbody> tags each with two rows.
		// the first is our data row; the second is our description.  so,
		// each individual $book in our array needs to be split into these
		// two rows.  luckily, our parent can handle a lot of that for us.
		
		$body = [];
		foreach ($books as $book) {
			
			// we very carefully arrange our queries such that the first
			// element of our records is always the ID number in the database
			// for the record.  that allows us to array_shift here removing
			// that datum from the array and allows our parent to handle the
			// rest of it.
			
			$rows["tbody_id"] = array_shift($book);
			$rows["description"] = $this->extractDescription($book);
			$rows["data"] = $this->extractData($book);
			$body[] = $rows;
		}
		
		return [
			"headers" => $header,
			"bodies"  => $body,
			"caption" => "The following are the SR books in this application.
				They're not all the published books for SR5; only those with
				quantifiable rules and stats are here.  Some, mostly the German
				content, are not included, i.e. you won't find their data
				elsewhere in the Shadowlab."
		];
	}

	protected function extractDescription(array $record, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// the default behavior of our parent's method is to include the abbr
		// in the description for the page reference.  but, in this case, the
		// abbr is data so we need to alter the array of descriptive keys that
		// our parent works with.
		
		return parent::extractDescription($record, ["description"]);
	}
	
	protected function extractData(array $record, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// like above, the parent would normally remove the abbr from our data
		// in favor of keeping it as a part of the description.  we'll want to
		// send the same list of descriptive keys here as we did above to avoid
		// this.
		
		return parent::extractData($record, ["description"]);
	}
	
	protected function transformOne(array $books): array {
		return $books;
	}
}
