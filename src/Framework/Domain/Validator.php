<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Validator\AbstractValidator;

/**
 * Class Validator
 *
 * This default validator simply invalidates all the things.  As children
 * need more specific validation, they can overwrite only those methods that
 * they need based on the expected behaviors of their domain.
 *
 * @package Shadowlab\Framework\Domain
 */
class Validator extends AbstractValidator {
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateCreate(array $data = []): bool {
		return false;
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateRead(array $data = []): bool {
		return false;
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateUpdate(array $data = []): bool {
		return false;
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateDelete(array $data = []): bool {
		return false;
	}
	
}
