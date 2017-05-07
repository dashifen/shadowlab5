<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Action\AbstractAction as DashifenAbstractAction;

/**
 * Class Action
 *
 * This is a default action class for the app.  It's still abstract because
 * it can't know how to execute the other, more specific actions.  But, what
 * it can do is ensure that required data can be passed to our response for
 * all of its children.
 *
 * @package Shadowlab\Action
 */
abstract class AbstractAction extends DashifenAbstractAction {
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleSuccess(array $data = []): void {
		
		// each of our handle* methods simply passes control down to the
		// respond method below.  that's the one that does our work, these
		// just tell it how to respond by passing the name of each of our
		// functions to it.  we use __FUNCTION__ instead of __METHOD__
		// because the latter includes the class name and we don't want
		// that.
		
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param string $function
	 * @param array  $data
	 *
	 * @return void
	 */
	protected function respond(string $function, array $data): void {
		
		// the purpose of this method -- indeed this entire object -- is
		// simply to ensure that we never forget to tell our response whether
		// or not we're authentic.  we can ask that object what's up and then
		// add it to our data.
		
		$authentic = $this->request->getSessionObj()->isAuthenticated() ? 1 : 0;
		$data = array_merge($data, ["authentic" => $authentic]);
		
		// we very specifically named our methods above so that they matched
		// the public methods of our response object.  that way we can use
		// a variable function call as follows to pass our newly authenticated
		// data over to it.
		
		$this->response->{$function}($data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleFailure(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleError(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleNotFound(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
}
