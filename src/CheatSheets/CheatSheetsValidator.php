<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Domain\AbstractValidator;

class CheatSheetsValidator extends AbstractValidator {
	
	protected function checkForOtherErrors(array $posted, array $schema): bool {
		
		// cheat sheets are very simple.  so simple, in fact, that there's
		// nothing to validate, because we don't do any updates within the
		// data.  so here, we return false, i.e. that there are no other
		// errors.
		
		return false;
	}
}
