<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Response\ShadowlabResponse;

class LoginResponse extends ShadowlabResponse {
	public function handleSuccess(array $data = [], string $action = 'read'): void {
		
		// handleSuccess is not actually a successful login in this case.
		// it's simply the page that show the login form.  there's not much
		// to do, really, other than set our data and load up the login
		// form.
		
		$this->setContent("login.html");
		$this->setData($data);
	}
	
	public function handleFailure(array $data = [], string $action = 'read'): void {
		
		// perhaps oddly, the success and failure responses include the same
		// content.  the information in our data is what's different.
		
		$this->setContent("login.html");
		$this->setData($data);
	}
	
	public function handleError(array $data = [], string $action = 'read'): void {
		
		// and, our error response is for when they've exceeded 5 login
		// attempts.  this will, hopefully, help to mitigate attempts to
		// brute force access to the system.
		
		$this->setStatusCode(401);						// unauthorized
		$this->setContent("login-failed.html");
		$this->setData($data);
	}
}
