<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Domain\Validator;

class SpellsValidator extends Validator {
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
}
