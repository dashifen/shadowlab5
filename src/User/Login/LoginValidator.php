<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Domain\Validator;

class LoginValidator extends Validator {
	public function validateRead(array $data = []): bool {
		return $this->confirmExpectations(["email","password"], array_keys($data));
	}
	
	public function validateUpdate(array $data = []): bool {
		return $this->confirmExpectations(["user_id","hashed","password"], array_keys($data));
	}
}
