<?php

namespace Shadowlab\User\Login;

use Dashifen\Domain\Validator\AbstractValidator;

class LoginValidator extends AbstractValidator {
	public function validateRead(array $data = []): bool {
		return $this->confirmExpectations(["email","password"], array_keys($data));
	}
	
	public function validateUpdate(array $data = []): bool {
		return $this->confirmExpectations(["user_id","hashed","password"], array_keys($data));
	}
	
	// because our Login handler should't be creating or deleting anything,
	// we're going to return false here.  no matter what might be sent here,
	// we don't want to do these actions, so any $data is invalid.
	
	public function validateCreate(array $data = []): bool {
		return false;
	}

	public function validateDelete(array $data = []): bool {
		return false;
	}
	
}
