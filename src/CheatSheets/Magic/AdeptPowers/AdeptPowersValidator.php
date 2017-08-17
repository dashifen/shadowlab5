<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Shadowlab\Framework\Domain\AbstractValidator;

class AdeptPowersValidator extends AbstractValidator {
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema): bool {
		
		// there are two "other" errors in this form:  if this power uses
		// levels to track costs and efficacy, then we need to know the
		// cost per level.  you'd think that we'd need to know the maximum
		// level, too, but if that remains NULL, then it's simply equal to
		// the character's Magic.
		
		if (($posted["levels"] ?? "N") === "Y") {
			$cost_per_level = $posted["cost_per_level"] ?? "";
			if (!is_numeric($cost_per_level)) {
				$this->validationErrors["cost_per_level"] = "This field is required because this power uses levels.";
				return true;
			}
		}
		
		return false;
	}
}
