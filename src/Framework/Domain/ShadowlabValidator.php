<?php

namespace Shadowlab\Framework\Domain;

class ShadowlabValidator extends AbstractValidator {
	/**
	 * @param array  $posted
	 * @param array  $schema
	 * @param string $action
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema, string $action): bool {
		
		// by returning false here we indicate to the Domain that there are
		// no other errors within our $posted data.  this is the common case
		// for most of our Handlers, but for those that do need to check for
		// other errors, they can always implement their own Validator.
		
		return false;
	}
	
}
