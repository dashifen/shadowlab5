<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Domain\Validator;

class CheatSheetsValidator extends Validator {
	public function validateRead(array $data = []): bool {
		
		// when reading from the database, the sheet type in our $data
		// better be empty or one of the known types of sheets that we're
		// displaying at this time.
		
		$type = $data["sheet_type"] ?? "";
		$types = ["", "magic", "matrix", "combat", "other"];
		return in_array($type, $types);
	}
}
