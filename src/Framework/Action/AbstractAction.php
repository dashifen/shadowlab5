<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Action\AbstractAction as DashifenAbstractAction;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Form\Builder\FormBuilderInterface;
use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\AddOns\SearchbarInterface;

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
	
	
	protected function read(): ResponseInterface {
		$payload = $this->domain->read([
			"recordId" => $this->recordId,
		]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"table"        => $payload->getDatum("records"),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"nextId"       => $payload->getDatum("nextId"),
				"searchbar"    => $this->getSearchbar($payload),
				"capabilities" => $this->request->getSessionVar("capabilities"),
				"singular"     => $this->getSingular(),
				"plural"       => $this->getPlural(),
				"caption"      => $this->getCaption(),
			]);
		} else {
			$noun = $payload->getDatum("count") === 1
				? $this->getSingular()
				: $this->getPlural();
			
			$this->handleFailure([
				"title" => "Perception Failed",
				"noun"  => $noun,
			]);
		}
		
		return $this->response;
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
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbarHtml = "";
		
		if ($payload->getDatum("count", 0) > 1) {
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->get("searchbar");
			$searchbar = $this->getSearchbarFields($searchbar, $payload);
			$searchbarHtml = $searchbar->getBar();
		}
		
		return $searchbarHtml;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	abstract protected function getSearchbarFields(
		SearchbarInterface $searchbar,
		PayloadInterface $payload
	): SearchbarInterface;
	
	/**
	 * @return string
	 */
	abstract protected function getSingular(): string;
	
	/**
	 * @return string
	 */
	abstract protected function getPlural(): string;
	
	/**
	 * @return string
	 */
	protected function getCaption(): string {
		
		// most of our collection tables don't need a caption.  so, by
		// default, we'll just return the empty string.  the children of
		// this object can make the changes to it that they need to when
		// they need to.
		
		return "";
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
	 * @return ResponseInterface
	 */
	protected function update(): ResponseInterface {
		
		// when we're updating the database, we actually do two things:
		// first we get data that we're going to update, then we save the
		// changes to those data when the visitor sends it back to us.
		
		$method = $this->request->getServerVar("REQUEST_METHOD") !== "POST"
			? "getDataToUpdate"
			: "savePostedData";
		
		return $this->{$method}();
	}
	
	protected function getDataToUpdate(): ResponseInterface {
		$payload = $this->domain->update([
			"recordId" => $this->recordId,
			"table"    => $this->getTable(),
		]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"title"        => "Edit " . $payload->getDatum("title"),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
				"singular"     => $this->getSingular(),
				"plural"       => $this->getPlural(),
				"errors"       => "",
			]);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
	}
	
	/**
	 * @return string
	 */
	abstract protected function getTable(): string;
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getForm(PayloadInterface $payload): string {
		
		// when one of our actions needs to show a form, it can call this
		// method along with a payload that describes the form needed to
		// produce it.
		
		/** @var FormBuilderInterface $formBuilder */
		
		$payloadData = $payload->getData();
		$formBuilder = $this->container->get("formBuilder");
		$payloadData["currentUrl"] = $this->request->getServerVar("SCRIPT_URL");
		$formBuilder->openForm($payloadData);
		$form = $formBuilder->getForm();
		
		// the $form that we have now, is the actual FormInterface object,
		// but what we want to send as a part of our response is the HTML for
		// it.  therefore, we call the form's getForm() method now, too.
		
		return $form->getForm(false);
	}
	
	protected function savePostedData(): ResponseInterface {
		$payload = $this->domain->update([
			"posted" => $this->request->getPost(),
			"idName" => $this->getRecordIdName(),
			"table"  => $this->getTable(),
		]);
		
		if ($payload->getSuccess()) {
			$data = $payload->getData();
			
			// we want to add some additional information necessary for
			// our view.  then, we slightly alter the title for our page
			// and send it all on its way.
			
			$data = array_merge($data, [
				"item"     => $data["title"],
				"singular" => $this->getSingular(),
				"plural"   => $this->getPlural(),
				"success"  => true,
			]);
			
			$data["title"] .= " Saved";
			$this->handleSuccess($data);
		} else {
			
			// if we encountered errors when validating our data before
			// putting it back into the database, we end up here.  we send
			// back the same information as we do when we first present the
			// form, but
			
			$this->handleError([
				"title"        => "Unable to Save Changes",
				"posted"       => $payload->getDatum("posted"),
				"errors"       => $payload->getDatum("errors"),
				"form"         => $this->getForm($payload),
				"singular"     => $this->getSingular(),
				"plural"       => $this->getPlural(),
				"instructions" => $this->getErrorInstructions(),
			]);
		}
		
		return $this->response;
	}
	
	/**
	 * @return string
	 */
	abstract protected function getRecordIdName(): string;
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleError(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @return string
	 */
	protected function getErrorInstructions(): string {
		return "We were unable to save the changes you made to this
			information in the database.  Use the error messages below
			and fix the problem(s) we encountered.  When you're ready,
			click the button to continue.  If this problem persists,
			email Dash.  It probably means he messed up the code
			somehow.";
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function delete(): ResponseInterface {
		$payload = $this->domain->delete([
			"recordId" => $this->recordId,
			"idName"   => $this->getRecordIdName(),
			"table"    => $this->getTable(),
		]);
		
		if ($payload->getSuccess()) {
			
			// when we successfully delete, we just want to re-show the
			// collection.  we can do this with a redirect response as
			// follows.
			
			$host = $this->request->getServerVar("HTTP_HOST");
			$url = $this->request->getServerVar("REQUEST_URI");
			$url = "http://$host" . substr($url, 0, strpos($url, "/delete"));
			$this->response->redirect($url);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
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
