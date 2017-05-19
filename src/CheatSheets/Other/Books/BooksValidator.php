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
}
