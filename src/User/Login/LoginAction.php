<?php

namespace Shadowlab\User\Login;

use Dashifen\Action\AbstractAction;
use Dashifen\Response\ResponseInterface;

/**
 * Class LoginAction
 *
 * @package Shadowlab\User\Login
 */
class LoginAction extends AbstractAction {
	/**
	 * @return ResponseInterface
	 */
	public function execute(): ResponseInterface {
		return $this->request->getServerVar("REQUEST_METHOD") === "POST"
			? $this->doAuthentication()
			: $this->doLogin();
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function doLogin() {
		
		// there isn't a failure case with respect to a simple login,
		// so we can just handle success here.  since we've not done
		// anything yet, there's no errors and
		
		$this->response->handleSuccess([
			"email"       => $this->request->getCookieVar("email"),
			"redirect_to" => $this->request->getSessionVar("redirect_to"),
			"errors"      => [],
		]);
		
		return $this->response;
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function doAuthentication() {
		
		// to authenticate, we'll get the posted username and password
		// and send them to our domain.  the domain will then send back
		// payload indicating success or failure.
		
		$email = $this->request->getPostVar("email");
		$password = $this->request->getPostVar("password");
		
		$payload = $this->domain->read([
			"email"    => $email,
			"password" => $password,
		]);
		
		if ($payload->getSuccess()) {
			$this->recordLogin($email, $payload->getDatum("user_id"));
			
			// when we successfully login, we want to redirect to the
			// index of our cheat sheets as follows.
			
			$host = $this->request->getServerVar("HTTP_HOST");
			$this->response->redirect("http://$host/cheat-sheets");
		} else {
			
			// if we were unsuccessful, then we need to see if this person
			// has exceeded the 5-tries limit to prevent brute force attacks.
			
			if ($this->isLimitExceeded()) {
				
				// there's only one error case for our login - that they've
				// exceeded the limit.  there are other failures (no account,
				// bad password, etc.) but those are handled in the else
				// below.
				
				$this->response->handleError();
			} else {
				
				// the payload has an error value that we'll simply pass
				// directly to the response here.  other than that, we just
				// want to be sure that we "remember" the email and the URL
				// to which they want to go after logging in.
				
				$this->response->handleFailure([
					"email"       => $email,
					"redirect_to" => $this->request->getPostVar("redirect_to"),
					"errors"      => $payload->getDatum("errors"),
				]);
			}
		}
		
		return $this->response;
	}
	
	/**
	 * @param string $email
	 * @param string $user_id
	 *
	 * @return void
	 */
	protected function recordLogin(string $email, string $user_id) {
		
		// to record the fact that this person has logged in, we're going
		// to get the session object from our request and then call its login
		// method.  we pass that method this individual's user name as well
		// as their user ID number.  their ID number may be used to record
		// their activities within the application later.
		
		$session = $this->request->getSessionObj();
		$session->login($email, ["user_id" => $user_id]);
	}
	
	/**
	 * @return bool
	 */
	protected function isLimitExceeded(): bool {
		$session = $this->request->getSessionObj();
		$attempts = $session->get("login_attempts", 0);
		
		// we'll increment our attempts and then see if they've tried
		// at least 5 times.  if so, we simply return true; they've
		// exceeded the limit.
		
		if (++$attempts >= 5) {
			return true;
		}
		
		// if not, then we set the login attempts to be this new value
		// in cause they get back here again later and return false.
		
		$session->set("login_attempts", $attempts);
		return false;
	}
}
