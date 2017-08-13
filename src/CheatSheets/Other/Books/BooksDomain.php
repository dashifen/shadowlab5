<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractDomain;

class BooksDomain extends AbstractDomain {
	public function read(array $data = []): PayloadInterface {
		
		// the only $data we expect to get from our action is an optional
		// abbreviation for a book.  so, we'll get the list of abbreviations
		// from the database and pass it all to the validator.
		
		$books = $this->db->getCol("SELECT book_id FROM books");
		$validation_data = array_merge($data, ["books" => $books]);
		if ($this->validator->validateRead($validation_data)) {
			
			// since we have one of two queries to run -- either for a specific
			// book or for all of them -- we'll just pass control from this
			// method to one of the two following ones.
			
			$payload = !empty($data["book_id"])
				? $this->readOne($data["book_id"])
				: $this->readAll();
			
			if ($payload->getSuccess()) {
				
				// our payload does need to be transformed for our view.
				// currently, it's just an array, but we need to prepare
				// it for display in our table or single views.
				
				$payload->setDatum("nextId", $this->getNextId());
				$payload = $this->transformer->transformRead($payload);
			}
		} else {
			
			// if our data were invalid, then we'll grab the errors that
			// our validator ran into and pass back a failed payload.
			
			$payload = $this->payloadFactory->newReadPayload(false, [
				"error" => $this->validator->getValidationErrors(),
			]);
		}
		
		return $payload;
	}
	
	protected function readOne(int $book_id): PayloadInterface {
		$sql = "SELECT book_id, book, description, abbr, included FROM books
			WHERE book_id = :book_id AND deleted = 0";
		
		$book = $this->db->getRow($sql, ["book_id" => $book_id]);
		
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
			abbr, included FROM books WHERE deleted = 0 ORDER BY book");
		
		// success here is simply selecting anything, and our title should
		// be more general than the one above.
		
		$count = sizeof($books);
		return $this->payloadFactory->newReadPayload($count > 0, [
			"title" => "Shadowrun Fifth Edition Books",
			"books" => $books,
			"count" => $count,
		]);
	}
	
	/**
	 * @return int
	 */
	protected function getNextId(): int {
		return $this->db->getVar("SELECT book_id FROM books
			WHERE description IS NULL ORDER BY book");
	}
	
	public function update(array $data): PayloadInterface {
		
		// the existence of a posted index within our $data determines
		// how we proceed.  if it's present, then we want to validate new
		// information for the database.  otherwise, we select old data
		// from it to facilitate changes to it when we return.
		
		return !isset($data["posted"])
			? $this->getDataToUpdate($data)
			: $this->savePostedData($data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	protected function getDataToUpdate(array $data): PayloadInterface {
		
		// if we're here, then we want to do a special sort of read. we
		// get a book ID from the visitor so here we want to read the
		// information about that book and then return that to our action.
		// luckily, we already have a method which does that.
		
		$books = $this->db->getCol("SELECT book_id FROM books");
		$validation_data = array_merge($data, ["books" => $books]);
		if ($this->validator->validateUpdate($validation_data)) {
			$payload = $this->readOne($data["book_id"]);
			if ($payload->getSuccess()) {
				
				// in order to build our form, our FormBuilder objects
				// need to have two additional payload data sent to them:
				// the database schema from which we've selected our data
				// and the index within our $payload where that data can
				// be found.  we call that later index "values" because it
				// represents the values found in (or going into) the
				// schema.
				
				$payload->setDatum("values", "books");
				$payload->setDatum("schema", $this->getTableDetails("books"));
				$payload = $this->transformer->transformUpdate($payload);
			}
			
			return $payload;
		}
		
		return $this->payloadFactory->newUpdatePayload(false, [
			"error" => $this->validator->getValidationErrors(),
		]);
	}
	
	protected function savePostedData(array $data): PayloadInterface {
		
		// to validate our posted data, we need to know more about the
		// table into which we're going to be inserting it.  so, we'll
		// get its schema and then pass everything over to our validator.
		
		$validationData = [
			"posted" => $data["posted"],
			"schema" => $this->getTableDetails("books"),
		];
		
		if ($this->validator->validateUpdate($validationData)) {
			
			// if we've validated our data, we're ready to put it into the
			// database.  right now, our posted data is a mix of the book_id
			// and the rest of the data that we want to update.  we'll grab
			// the ID and then use array_filter() to collapse the rest of it
			// into the data for our update.
			
			$bookId = $data["posted"]["book_id"];
			$bookData = array_filter($data["posted"], function($index) {
				return $index !== "book_id";
			}, ARRAY_FILTER_USE_KEY);
			
			$this->db->update("books", $bookData, ["book_id" => $bookId]);
			
			return $this->payloadFactory->newUpdatePayload(true, [
				"title"  => $data["posted"]["book"],
				"nextId" => $this->getNextId(),
				"thisId" => $bookId,
			]);
		}
		
		// if we didn't return within the if-block above, then we want
		// to report a failure to update.  we want to be sure to send back
		// the information necessary to re-display our form along with the
		// posted data, too.  then, a quick pass through our transformer,
		// just in case it's necessary we do so, and we're done here.
		
		$payload = $this->payloadFactory->newUpdatePayload(false, [
			"error"  => $this->validator->getValidationErrors(),
			"schema" => $validationData["schema"],
			"posted" => $validationData["posted"],
			"values" => "posted",
		]);
		
		return $this->transformer->transformUpdate($payload);
	}
	
	
}
