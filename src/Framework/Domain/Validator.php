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
	
	/**
	 * @param mixed $found_error
	 * @param mixed $error
	 *
	 * @return bool
	 */
	protected function findErrors($found_error, $error = null) {
		
		// this is an odd function that breaks the rules for 7.1
		// and type checking because we want to use it both as the
		// interface to and the call back for an array_reduce()
		// process.
		
		if (is_array($found_error)) {
			
			// if the $found_error parameter is an array, then we want
			// to trigger our reduction using this method as the callback.
			// this'll use the else block below to analyze the values in
			// the array.  notice that the initial value for our reduction
			// is false and the else block only sets it to true so our
			// return type is assured.
			
			return array_reduce($found_error, [$this, "findErrors"], false);
		} else {
			
			// otherwise, we should be dealing with our reduction.  in
			// this case, the $error parameter is an index within the array
			// this case, the $error parameter is an index within the array
			// originally passed here.  if that value is not Boolean false,
			// then it represents an error and we set the $found_error flag.
			
			if ($error !== false) {
				$found_error = true;
			}
			
			return $found_error;
		}
	}
}
