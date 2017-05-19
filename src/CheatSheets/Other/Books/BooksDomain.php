<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\Domain;

class BooksDomain extends Domain {
	public function read(array $data = []): PayloadInterface {
		
		// the only $data we expect to get from our action is an optional
		// abbreviation for a book.  so, we'll get the list of abbreviations
		// from the database and pass it all to the validator.
		
		$books = $this->db->getCol("SELECT book_id FROM books");
		$validation_data = array_merge($data, ["books"=>$books]);
		if ($this->validator->validateRead($validation_data)) {
			
			// since we have one of two queries to run -- either for a specific
			// book or for all of them -- we'll just pass control from this
			// method to one of the two following ones.
			
			$payload = !empty($data["abbr"])
				? $this->readOne($data["abbr"])
				: $this->readAll();
			
			if ($payload->getSuccess()) {
				
				// our payload does need to be transformed for our view.
				// currently, it's just an array, but we need to prepare
				// it for display in our table or single views.
				
				$payload = $this->transformer->transformRead($payload);
			}
		} else {
			
			// if our data were invalid, then we'll grab the errors that
			// our validator ran into and pass back a failed payload.
			
			$payload = $this->payloadFactory->newReadPayload(false, [
				"error" => $this->validator->getValidationErrors()
			]);
		}
		
		return $payload;
	}
	
	protected function readOne(string $abbr): PayloadInterface {
		$sql = "SELECT book_id, book, description, abbr, included FROM books
			WHERE abbr = :abbr";
		
		$book = $this->db->getRow($sql, ["abbr" => $abbr]);
		
		// success is when we can read anything.  since we use getRow, the
		// size of the array is the number of columns, not the number of
		// sets of columns returned.  so, as long as we have data, then we're
		// gonna assume it's the right data for now.

		return $this->payloadFactory->newReadPayload(sizeof($book) > 0, [
			"title" => $book["book"],
			"books" => $book,
			"count" => 1,
		]);
	}
	
	protected function readAll(): PayloadInterface {
		$books = $this->db->getResults("SELECT book_id, book, description,
			abbr, included FROM books ORDER BY book");
		
		// success here is simply selecting anything, and our title should
		// be more general than the one above.
		
		$count = sizeof($books);
		return $this->payloadFactory->newReadPayload($count > 0, [
			"title" => "Shadowrun Fifth Edition Books",
			"books" => $books,
			"count" => $count,
		]);
	}
}
