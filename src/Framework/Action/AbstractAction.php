<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Action\AbstractAction as DashifenAbstractAction;
use Dashifen\Response\ResponseInterface;

/**
 * Class Action
 *
 * @package Shadowlab\Action
 */
abstract class AbstractAction extends DashifenAbstractAction {
	protected const ACTION_CAPABILITIES = [
		"create" => "fixer",
		"read"   => "runner",
		"update" => "fixer",
		"delete" => "johnson",
	];

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
	 *
	 * @return ResponseInterface
	 * @throws ActionException
	 */
	public function execute(array $parameter = []): ResponseInterface {
		$this->processParameter($parameter);
		
		// before we continue, we have to be sure that the current visitor
		// has the appropriate capabilities to perform the requested action.
		// if they do not, we want to return a response that'll take us to
		// the unauthorized view.
		
		if (!$this->userIsAuthorized()) {
			$this->response->handleError(["httpError" => "Unauthorized"]);
			return $this->response;
		}
		
		// if we're still here, then we're authorized to perform this
		// action.  the last thing we have to do is be sure that our action
		// handler exists and, if so, we call it.  otherwise, we throw a
		// tantrum.
		
		if (!method_exists($this, $this->action)) {
			throw new ActionException(
				"Unknown action handler: $this->action",
				ActionException::UNKNOWN_ACTION_HANDLER
			);
		}
		
		return $this->{$this->action}();
	}
	
	/**
	 * @param array $parameter
	 *
	 * @throws ActionException
	 */
	protected function processParameter(array $parameter = []) {
		if (sizeof($parameter) > 0) {
			
			// the wildcard pattern we use to identify our action parameters
			// results in either one or three matching groups.  first, we pad
			// it to 3 so that we homogenize that length.  then, we can use
			// list() to get at those data.
			
			$parameter = array_pad($parameter, 3, "");
			list($createAction, $otherAction, $recordId) = $parameter;
			
			// now, if our $otherAction is empty, we assume that we're
			// reading.  then, to determine the action, if it's a create
			// action, that takes precedence over the other actions.
			
			$action = empty($createAction)
				? (empty($otherAction) ? "read" : $otherAction)
				: "create";
			
			$this->setAction($action);
			
			// finally, if we have a numeric record ID, we'll set that
			// information, too.  otherwise, we'll assume that the record
			// ID is unnecessary or, as with a create action, that it can
			// remain the default of zero.
			
			if (!is_numeric($recordId) || floor($recordId) != $recordId) {
				throw new ActionException(
					"Invalid record ID: $recordId",
					ActionException::INVALID_RECORD_ID
				);
			}
			
			$this->setRecordId($recordId);
		}
	}
	
	/**
	 * @param string $action
	 *
	 * @return void
	 * @throws ActionException
	 */
	protected function setAction(string $action): void {
		
		// in many apps, the idea of an update and a patch action would
		// seem redundant.  but, the update action is the one used when
		// we're getting information about a record and a patch is used
		// when we're sending information back to be saved in the
		// database.
		
		if (in_array($action, ["create", "read", "update", "delete"])) {
			$this->action = $action;
			return;
		}
		
		throw new ActionException(
			"Unknown action: $action.",
			ActionException::UNKNOWN_ACTION
		);
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
	 * @return bool
	 */
	protected function userIsAuthorized(): bool {
		
		// here we want to compare our current action to the necessary
		// capability for each of them.  we have a protected constant
		// defined above that tells us which cap this visitor needs, we
		// can then see if it's defined in our session for them.
		
		$capability = self::ACTION_CAPABILITIES[$this->action];
		$capabilities = $this->request->getSessionVar("capabilities");
		return isset($capabilities[$capability]);
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
		// data over to it.  notice we pass our action property over to the
		// response; it's optional, but sometimes they need it.
		
		$this->response->{$function}($data, $this->action);
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
