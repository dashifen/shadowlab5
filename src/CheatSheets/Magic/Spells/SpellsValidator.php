<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Domain\AbstractValidator;

class SpellsValidator extends AbstractValidator {
	public function validateRead(array $data = []): bool {
	
		// from the domain we get an individual spell ID number as well
		// as the list of all such numbers.  the former must be either
		// empty or found within the latter for us to have valid read
		// data.
		
		$valid = empty($data["spell_id"]) || in_array($data["spell_id"], $data["spells"]);
		
		if (!$valid) {
			$this->validationErrors["spell_id"] = "Unable to find spell in database.";
		}
	
		return $valid;
	}
	
	public function validateUpdate(array $data = []): bool {
		
		// to validate our update, we need to determine if we have posted
		// data or not.  if not, then we can just validate that we've got
		// what we need to read about a spell in the database.
		
		return isset($data["posted"])
			? $this->checkForCommonErrors(...array_values($data))
			: $this->validateRead($data);
	}
}
