<?php

namespace Shadowlab\User\Login;

use Dashifen\Domain\AbstractMysqlDomain;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\DomainException;

/**
 * Class LoginDomain
 *
 * Like its Action, the LoginDomain doesn't need the bells and whistles
 * set up for the cheat sheet domains.  so, we just sort of skip over
 * them and directly extend the AbstractMysqlDomain of our framework.
 *
 * @package Shadowlab\User\Login
 */
class LoginDomain extends AbstractMysqlDomain {
	public function create(array $data = []): PayloadInterface {
		throw new DomainException("Unexpected LoginDomain Behavior: Create",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	public function read(array $data = []): PayloadInterface {
		if ($this->validator->validateRead($data)) {
			
			// the information we expect to receive from the visitor
			// is their username and password.  with those, we can check
			// the database for a matching account.  we'll select their
			// user ID and the password in our database and then we can
			// verify that the passwords match.
			
			$sql = "SELECT user_id, password AS hashed, email, role
				FROM users WHERE email=:email";
			
			$results = $this->db->getRow($sql, ["email" => $data["email"]]);
			
			if (sizeof($results) !== 0) {
				
				// if we received a result, then the account exists.  so,
				// now we have to be sure that the passwords match:
				
				if (password_verify($data["password"], $results["hashed"])) {
					
					// we're good to go!  but, we've one more thing to do
					// here: update this person's last login date.  the other
					// method will handle that for us.
					
					$this->update(array_merge($data, $results));
					$results["capabilities"] = $this->getCapabilities($results);
					return $this->payloadFactory->newReadPayload(true, $results);
				}
			}
		}
		
		// if we're still here, then we didn't return a successful payload
		// in the if-block above.  so, we want to return an failed read
		// payload with an error message.  that message is intentionally vague
		// to help avoid cluing a hacker into what might have happened here.
		
		$results["error"] = "We were unable to log you in.  Please try again.";
		return $this->payloadFactory->newReadPayload(false, $results);
	}
	
	protected function getCapabilities(array $results): array {
		
		// there are various roles within the system starting with the
		// "johnson" role and going down to "goon" (e.g. administrator
		// to guest).  anyone has the capability of their role and the
		// ones below it.  here, we want to return a map of the roles
		// that this person can play:
		
		$roles = $this->db->getEnumValues("users", "role");
		
		$foundRole = false;
		$capabilities = [];
		foreach ($roles as $role) {
			if ($role === $results["role"]) {
				
				// once we find the match for this visitor's role, we'll
				// set our flag.
				
				$foundRole = true;
			}
			
			// then, for that matching role and all subsequent ones (the
			// ENUM field's values are enumerated in descending order of
			// significance), the set flag will indicate that the visitor
			// can act at certain capability levels.  prior to the match,
			// our unset flag will ensure that a visitor doesn't have the
			// capabilities of our roles.
			
			$capabilities[$role] = $foundRole;
		}
		
		return $capabilities;
	}
	
	public function update(array $data): PayloadInterface {
		
		// when we're here, our primary reason is to update the last-login
		// date for the current visitor.  but, we'll also check to see if we
		// need to rehash the visitor's password.  our $data comes from the
		// read method above, but we'll check it anyway.
		
		$success = false;
		if ($this->validator->validateUpdate($data)) {
			
			// ordinarily, we only update a date and we don't need to specify
			// a value because we can use a MySQL function to make the change.
			// we'll start that process now.
			
			$values = ["user_id" => $data["user_id"]];
			$sql = "UPDATE users SET last_login = NOW()";
			if (password_needs_rehash($data["hashed"], PASSWORD_DEFAULT)) {
				
				// if we're in here, then we also need to update this person's
				// hashed password.  we'll construct the new hash and then add
				// to our $sql and $values.
				
				$values["hashed"] = password_hash($data["password"], PASSWORD_DEFAULT);
				$sql .= ", password=:hashed";
			}
			
			// now we just finish off our SQL query and then run it.  because
			// we're using a function to set our data, we can't use the update()
			// method of our database; we'll just use runQuery directly.
			
			$sql .= " WHERE user_id=:user_id";
			$success = $this->db->runQuery($sql, $values);
		}
		
		return $this->payloadFactory->newUpdatePayload($success);
	}
	
	public function delete(array $data): PayloadInterface {
		throw new DomainException("Unexpected LoginDomain Behavior: Delete",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
}
