<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Action\AbstractAction as DashifenAbstractAction;

/**
 * Class Action
 *
 * @package Shadowlab\Action
 */
abstract class AbstractAction extends DashifenAbstractAction {
	/**
	 * @var string $action
	 */
	protected $action = "read";
	
	/**
	 * @var int $recordId
	 */
	protected $recordId = 0;
	
	/**
	 * @param array $parameter
	 */
	protected function processParameter(array $parameter = []) {
		if (sizeof($parameter) > 0) {
			
			// if we have information to process, then we want to
			// remove the empty values.  we're then left with our
			// action and record ID number.
			
			$parameter = array_filter($parameter);
			$this->setAction(array_shift($parameter));
			
			if (sizeof($parameter) > 0) {
				$this->setRecordId(array_shift($parameter));
			}
		}
	}
	
	/**
	 * @param string $action
	 *
	 * @return void
	 * @throws ActionException
	 */
	protected function setAction(string $action): void {
		if (in_array($action, ["create", "read", "update", "delete"])) {
			$this->action = $action;
			return;
		}
		
		throw new ActionException("Unknown action: $action.");
	}
	
	/**
	 * @param int $recordId
	 *
	 * @return void
	 */
	protected function setRecordId(int $recordId) {
		$this->recordId = $recordId;
	}
	
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
