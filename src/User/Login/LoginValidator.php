<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Domain\AbstractValidator;

class LoginValidator extends AbstractValidator {
	public function validateRead(array $data = []): bool {
		
		// when reading, we don't actually have to check for record IDs and
		// records when logging in.  so, we can simply confirm that we have
		// the data we need as follows.
		
		return $this->confirmExpectations(["email","password"], array_keys($data));
	}
	
	public function validateUpdate(array $data = []): bool {
		
		// at this time, the only updating we're doing is the automatic
		// updating of hashed passwords as PHP's default password hashing
		// system gets updated.  so, all we need to do here is confirm
		// that we get what we need to do so.
		
		return $this->confirmExpectations(["user_id","hashed","password"], array_keys($data));
	}
	
	/**
	 * @param array  $posted
	 * @param array  $schema
	 * @param string $action
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema, string $action): bool {
		
		// at this time, we're not updating or creating accounts, so we
		// can't have other errors beyond what the parent object can handle.
		// so, we can just return false here.
		
		return false;
	}
}
