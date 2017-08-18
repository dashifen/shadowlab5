<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Validator\AbstractValidator AS DashifenAbstractValidator;

/**
 * Class Validator
 *
 * @package Shadowlab\Framework\Domain
 */
abstract class AbstractValidator extends DashifenAbstractValidator {
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
		
		// when validating a read action, we optionally receive a recordId
		// and a set of records.  our read action is valid with or without
		// an ID, but if we have an ID, that ID must be in the set of records.
		
		$valid = empty($data["recordId"]) || in_array($data["recordId"], $data["records"] ?? []);
		$this->validationErrors["recordId"] = !$valid ? "Unknown record" : false;
		return $valid;
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateUpdate(array $data = []): bool {
		
		// like all of our update actions, validation takes two forms:
		// validating that we can read the right data from the database
		// and validating that we've received the right data from the
		// client.  we can tell the difference between these two
		// behaviors based on the existence of a recordId having been
		// sent here in our $data.
		
		$methods = !isset($data["recordId"])
			
			// when the ID is set, then we need to validate that our record
			// exists and that we know what table it "lives" in.  our other
			// methods below will handle that for us.
		
			? ["validatePostedData", "validateIdName", "validateTable"]
			
			// when we're validating posted data, we want to check into it,
			// that we have know the name of our ID column (so we can build
			// a key for our update statement), and that we have the name of
			// the table from which our data is select
			
			: ["validateRecordExists", "validateTable"];
		
		foreach ($methods as $method) {
			if (!$this->{$method}($data)) {
				
				// since all of our validation tests must be true, the first
				// one that we hit that is invalid, we can return false to
				// help save a little time.
				
				return false;
			}
		}
		
		// if we got here, all of our tests passed, so we can return true.
		
		return true;
	}
	
	
	protected function validatePostedData(array $data) {
		
		// we expect that children will likely have to extend this method
		// to handle the unique validation needs for their data, but we can,
		// at least, check for common errors.  in fact, we have a method
		// that does just that.  hell, it's named that!
		
		$posted = $data["posted"] ?? [];
		$schema = $data["schema"] ?? [];
		
		if (sizeof($posted) === 0) {
			$this->validationErrors["posted"] = "Missing posted data";
		} elseif (sizeof($schema) === 0) {
			$this->validationErrors["schema"] = "Missing table data";
		} else {
			
			// if we have both posted data and schema for its table, we
			// call the method below to check for our common errors.  we'll
			// also require use an abstract method to check for other, less
			// common errors.  neither of these two checks return a true
			// value, then we'll be good to go.
			
			$commonErrors = $this->checkForCommonErrors($posted, $schema);
			$otherErrors = $this->checkForOtherErrors($posted, $schema);
			return !$commonErrors && !$otherErrors;
		}
		
		// if we're here, we're missing either posted data or schema.
		// either way, we're not valid and we just return false.
		
		return false;
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
		
		$foundErrors = false;
		foreach ($schema as $column => $columnData) {
			if ($column !== "guid" && $column !== "deleted") {
				$value = $posted[$column] ?? null;
				$this->validationErrors[$column] = false;
				
				// what we look for here is whether we have a value for the
				// required fields.  then, if we have a maximum length we'll
				// also test for that.  for both of these, we need the length
				// of our $value.  we can't use empty() because empty("0") is
				// true even though "0" might be a legitimate response.
				
				$required = $columnData["IS_NULLABLE"] === "NO";
				$maxlength = $columnData["CHARACTER_MAXIMUM_LENGTH"];
				$length = is_array($value) ? sizeof($value) : strlen($value);
				
				if ($required && $length === 0) {
					$this->validationErrors[$column] = "This field is required.";
				} elseif (is_numeric($maxlength) && $length > $maxlength) {
					$this->validationErrors[$column] = "Your entry is too long.";
				} elseif (sizeof($columnData["OPTIONS"]) > 0) {
					
					// if our field has options, we're working with a SelectOne
					// or SelectMany field.  we need to be sure that our value(s)
					// can be found within the keys of our options.  then, to
					// homogenize what we need to do for both fields, we'll make
					// sure our $value becomes an array if it isn't one.
					
					$keys = array_keys($columnData["OPTIONS"]);
					$values = is_array($value) ? $value : [$value];
					$values = array_filter($values);
					
					if (sizeof($values)) {
						foreach ($values as $value) {
							if (!in_array($value, $keys)) {
								$this->validationErrors[$column] = "Your response was invalid.";
							}
						}
					}
				}
				
				// and, finally, if we've set our error message, then we'll
				// make sure that our $foundErrors flag is set as well.  that
				// becomes our return value below.
				
				if ($this->validationErrors[$column] !== false) {
					$foundErrors = true;
				}
			}
		}
		
		return $foundErrors;
	}
	
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	abstract protected function checkForOtherErrors(array $posted, array $schema): bool;
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateIdName(array $data): bool {
		
		// all we need to do here is confirm the expectation that an idName
		// index is sent here so that we know what the name of our primary
		// key ID column is.
		
		return $this->confirmExpectations(["idName"], array_keys($data));
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateTable(array $data): bool {
		
		// like the prior method, this one simply requires that we have a
		// table name as expected.  we let other processes confirm that the
		// table exists.
		
		return $this->confirmExpectations(["table"], array_keys($data));
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateRecordExists(array $data): bool {
		
		// validating that a record exists means that we to meet the
		// expectation that our record ID and a set of records exists.
		// then, the ID must also be in those records.  first, we'll
		// confirm our expectation; then, the record's existence.
		
		$this->validationErrors["recordId"] = false;
		$confirmed = $this->confirmExpectations(["recordId", "records"], array_keys($data));
		
		if ($confirmed) {
			$this->validationErrors["recordId"] = !in_array($data["recordId"], $data["records"])
				? "Requested record does not exist"
				: false;
		} else {
			$this->validationErrors["recordId"] = "Missing record information";
		}
		
		// now, if our validation error is false, then everything is good
		// to go.  but if it's anything other than false, we need to let the
		// calling scope know that our records existence is not validated.
		// i.e., if our error is false, then our validation is true.
		
		return $this->validationErrors["recordId"] === false;
	}
	
	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateDelete(array $data = []): bool {
		
		// validating that we can delete a thing means knowing that the
		// thing exists and that we know from what table it should be
		// deleted.  we also need to know the name of the thing.  luckily,
		// we already have functions for this.
		
		return $this->validateRecordExists($data)
			&& $this->validateTable($data);
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
