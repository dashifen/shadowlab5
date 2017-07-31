<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Domain\Validator;

class BooksValidator extends Validator {
	public function validateRead(array $data = []): bool {
		
		// to validate, what we get from the domain is a list of all book
		// abbreviations in the database as well as the abbreviation for the
		// book the visitor requested.  either (a) we need to not have such
		// a requested abbreviation or (b) it must be in the list.
		
		$valid = empty($data["book_id"]) || in_array($data["book_id"], $data["books"]);
		
		if (!$valid) {
			$this->validationErrors["book_id"] = "Unable to find abbreviation in database.";
		}
		
		return $valid;
	}
	
	public function validateUpdate(array $data = []): bool {
		
		// this method gets called both when we want to validate that we
		// have a valid book to update and when we want to validate data
		// to save in the database.  the structure of $data will tell us
		// which is which.  when reading information about a book, we can
		// use the method above as our validation.  otherwise, we'll
		// continue below.
		
		if (isset($data["book_id"])) {
			return $this->validateRead($data);
		}
		
		// if we're still here, then we want to actually validate our
		// posted data.  for books, this is pretty straight forward.
		
		$valid = true;
		
		
		
		// for our patch, we're going to assume that all is well until
		// proven otherwise.  thus,
		
		
		return $valid;
	}
}
