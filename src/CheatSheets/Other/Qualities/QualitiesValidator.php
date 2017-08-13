<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\AbstractValidator;

class QualitiesValidator extends AbstractValidator {
	public function validateRead(array $data = []): bool {
		
		// to validate a read, we get a list of all quality IDs and, when
		// requesting a single quality, the ID of the one we want, so we're
		// valid either if we don't have such an ID (i.e. we're requesting
		// all of them) or when the ID we have is in the list.
		
		$qualityId = $data["quality_id"] ?? null;
		$valid = empty($qualityId) || in_array($qualityId, $data["qualities"] ?? []);
		$this->validationErrors["quality_id"] = !$valid ? "Unknown quality ID" : false;
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
}
