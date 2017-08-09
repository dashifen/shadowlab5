<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\Validator;

class QualitiesValidator extends Validator {
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
}
