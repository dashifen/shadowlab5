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
	
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	protected function checkForCommonErrors(array $posted, array $schema): bool {
		
		// given $posted data and the table $schema in which that data will
		// be saved, this method looks for missing required data, data that's
		// too long, and data that cannot be found within a set of valid
		// options.
		
		$valid = true;
		foreach ($schema as $column => $columnData) {
			$value = $posted[$column] ?? null;
			$this->validationErrors[$column] = false;
			
			// what we look for here is whether we have a value for the
			// required fields.  then, if we have a maximum length we'll
			// also test for that.  for both of these, we need the length
			// of our $value.  we can't use empty() because empty("0") is
			// true even though "0" might be a legitimate response.
			
			$length = strlen($value);
			$required = $columnData["IS_NULLABLE"] === "NO";
			$maxlength = $columnData["CHARACTER_MAXIMUM_LENGTH"];
			
			if ($required && $length === 0) {
				$this->validationErrors[$column] = "This field is required.";
			} elseif (is_numeric($maxlength) && $length > $maxlength) {
				$this->validationErrors[$column] = "Your entry is too long.";
			} elseif (sizeof($columnData["OPTIONS"]) > 0) {
				
				// the last error we want to test for is a $value that's not
				// found within the set of appropriate options for that value.
				// remember: the valid values are the keys of the OPTIONS
				// index; the values within that array are what's displayed
				// on-screen
				
				if (!in_array($value, array_keys($columnData["OPTIONS"]))) {
					$this->validationErrors[$column] = "This response is not valid.";
				}
			}
			
			// and, finally, if we've set our error message, then we'll make
			// sure that our valid flag is set, too.  by ANDing the value of
			// $valid with our test, we can avoid a single-line if-block and
			// still get the result we want.  plus, once we hit the first
			// error, the AND comparison will short circuit and we won't have
			// to test the array value against false anymore
			
			$valid = $valid && $this->validationErrors[$column] !== false;
		}
		
		return $valid;
	}
}
