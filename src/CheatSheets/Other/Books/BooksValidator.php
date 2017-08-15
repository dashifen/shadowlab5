<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Domain\AbstractValidator;

class BooksValidator extends AbstractValidator {
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema): bool {
		
		// like most of our record forms, the books form is covered by
		// our common error checker.  so, we can return false here to
		// indicate that there are no additional errors.
		
		return false;
	}
}
