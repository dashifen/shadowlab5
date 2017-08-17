<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Response\AbstractResponse;

class LoginResponse extends AbstractResponse {
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerTemplate(string $template): string {
		
		// success or failure, our template is the login.html view.  so,
		// here we ignore whatever our parent object determined the template
		// to be and simply return the one we need unless our parent sent
		// us the error view; that one we still use.
		
		return $template === "error.html" ? $template : "login.html";
	}
	
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	protected function getHandlerSuccessTemplate(string $template): string {
		
		// there's two success states for our action:  someone visiting the
		// page to enter their credentials (i.e. a successful request for the
		// form) and a successful authentication.  the former requires the
		// login.html view, the latter redirects.  so, we can just return the
		// login view here and let the Action take over from there.
		
		return "login.html";
	}
}
