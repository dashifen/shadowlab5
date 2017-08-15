<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Domain\AbstractValidator;

class SpellsValidator extends AbstractValidator {
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema): bool {
		
		// there are no errors beyond those which are recognized by our
		// parent object.  so, here we can simply return false.
		
		return false;
	}
	
}
