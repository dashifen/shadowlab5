<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Domain\Validator;

class BooksValidator extends Validator {
	public function validateRead(array $data = []): bool {
		
		// to validate a read, we get a list of all book IDs and, when
		// requesting a single book, the ID of the one we want, so we're
		// valid either if we don't have such an ID (i.e. we're requesting
		// all books) or when the ID we have is in the list.
		
		$bookId = $data["book_id"] ?? null;
		$valid = empty($bookId) || in_array($bookId, $data["books"] ?? []);
		$this->validationErrors["book_id"] = !$valid ? "Unknown book ID" : false;
		return $valid;
	}
	
	public function validateUpdate(array $data = []): bool {
		
		// this method gets called both when we want to validate that we
		// have a valid book to update and when we want to validate data
		// to save in the database.  the structure of $data will tell us
		// which is which.  when we have posted data, then we want we can
		// rely on the check for common errors because our form is fairly
		// straightforward this time.  otherwise, we need to be sure we
		// can read the book we're working to update using the method
		// above.
		
		return isset($data["posted"])
			? $this->checkForCommonErrors(...array_values($data))
			: $this->validateRead($data);
	}
	
	public function	validateDelete(array $data = []): bool {
		
		// this is similar to validating a read action:  we get an ID and
		// it better be in our list.  the difference is that the ID is
		// required; it's optional during a read.
		
		$bookId = $data["book_id"];
		$valid = is_numeric($bookId) && in_array($bookId, $data["books"] ?? []);
		$this->validationErrors["book_id"] = !$valid ? "Invalid book ID: $bookId" : false;
		return $valid;
	}
}
