<?php

namespace Shadowlab\User\Login;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;

/**
 * Class LoginAction
 *
 * @package Shadowlab\User\Login
 */
class LoginAction extends AbstractAction {
	/**
	 * @param array $parameter
	 *
	 * @return ResponseInterface
	 */
	public function execute(array $parameter = []): ResponseInterface {
		if ($this->request->getSessionObj()->isAuthenticated()) {
			
			// if we're here but already authentic, we can just go to
			// our sheets without worrying about anything else.
			
			$this->redirectToSheets();
		} else {
			
			// otherwise, we'll have to either process the visitor's
			// credentials or simply request that they provide them to
			// us based on the method that brought us here.
			
			$this->request->getServerVar("REQUEST_METHOD") === "POST"
				? $this->doAuthentication()
				: $this->doLogin();
		}
		
		return $this->response;
	}
	
	/**
	 * @return void
	 */
	protected function redirectToSheets(): void {
		
		// to redirect to our cheat sheets, we need to know which host
		// we're currently using.  then, we can just tell our response
		// that it's a redirection to that host at the /cheat-sheets
		// route.
		
		$host = $this->request->getServerVar("HTTP_HOST");
		$this->response->redirect("http://$host/cheat-sheets");
	}
	
	/**
	 * @return void
	 */
	protected function doAuthentication(): void {
		
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
			
			// now that we're authenticated, we want to record our log in
			// and then redirect to our sheets.
			
			$this->recordLogin($payload);
			$this->redirectToSheets();
		} else {
			
			// if we were unsuccessful, then we need to see if this person
			// has exceeded the 5-tries limit to prevent brute force attacks.
			
			if ($this->isLimitExceeded()) {
				
				// there's only one error case for our login - that they've
				// exceeded the limit.  there are other failures (no account,
				// bad password, etc.) but those are handled in the else
				// below.
				
				$this->handleError([
					"title" => "Unauthorized Access",
				]);
			} else {
				
				// the payload has an error value that we'll simply pass
				// directly to the response here.  other than that, we just
				// want to be sure that we "remember" the email and the URL
				// to which they want to go after logging in.
				
				$this->handleFailure([
					"title"       => "Login Failed",
					"email"       => $email,
					"redirect_to" => $this->request->getPostVar("redirect_to"),
					"honeypot"    => $this->request->getPostVar("honeypot"),
					"attempts"    => $this->request->getSessionVar("login_attempts"),
					"error"       => $payload->getDatum("error"),
				]);
			}
		}
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return void
	 */
	protected function recordLogin(PayloadInterface $payload): void {
		
		// to record the fact that this person has logged in, we're going
		// to get the session object from our request and then call its login
		// method.  we pass that method this individual's user name as well
		// as their user ID number.  their ID number may be used to record
		// their activities within the application later.
		
		$session = $this->request->getSessionObj();
		$session->login($payload->getDatum("email"), [
			"user_id"      => $payload->getDatum("user_id"),
			"capabilities" => $payload->getDatum("capabilities"),
			"role"         => $payload->getDatum("role"),
		]);
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
	
	/**
	 * @return void
	 */
	protected function doLogin(): void {
		
		// there isn't a failure case with respect to a simple login,
		// so we can just handle success here.  since we've not done
		// anything yet, there's no errors and
		
		$this->handleSuccess([
			"title"       => "Login",
			"email"       => $this->request->getCookieVar("email"),
			"redirect_to" => $this->request->getSessionVar("redirect_to"),
			"attempts"    => $this->request->getSessionVar("login_attempts", 0),
			"honeypot"    => "",
		]);
	}
}
