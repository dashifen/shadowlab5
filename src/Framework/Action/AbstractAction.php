<?php

namespace Shadowlab\Framework\Action;

use Aura\Di\Container;
use Aura\Di\Exception\ServiceNotFound;
use Dashifen\Action\AbstractAction as DashifenAbstractAction;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Form\Builder\FormBuilderInterface;
use Dashifen\Request\RequestInterface;
use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Domain\ShadowlabDomainInterface;
use Dashifen\Domain\MysqlDomainException;
use Dashifen\Response\ResponseException;

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
	 * @var ShadowlabDomainInterface $domain
	 *
	 * we don't have to redeclare this property, but doing so helps the
	 * IDE understand that we've extended the DomainInterface to create
	 * the ShadowlabDomainInterface, and we need an object that implements
	 * the latter.
	 */
	protected $domain;
	
	/**
	 * AbstractAction constructor.
	 *
	 * @param RequestInterface         $request
	 * @param ShadowlabDomainInterface $domain
	 * @param ResponseInterface        $response
	 * @param Container                $container
	 */
	public function __construct(
		RequestInterface $request,
		ShadowlabDomainInterface $domain,
		ResponseInterface $response,
		Container $container
	) {
		parent::__construct($request, $domain, $response, $container);
	}
	
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
			// as four matching groups.  but, they're not always present
			// depending on our route.  so, we'll pad it to the expected
			// length.  notice we use zero since (a) zero is still empty()
			// and (b) it'll be a good record ID for create actions.
			
			$parameter = array_pad($parameter, 4, 0);
			list($createAction, $otherAction, $recordId, $sheetType) = $parameter;
			
			
			// if we have a sheet type, then it takes precedence.  plus, our
			// pattern only matches the sheet types we know about, so we don't
			// need to test it beyond existence.
			
			if (!empty($sheetType)) {
				$this->setRecordId($this->domain->getSheetTypeId($sheetType));
				$this->setAction("read");
			} else {
				
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
	 * @param PayloadInterface $payload
	 *
	 * @return array
	 */
	protected function getBookOptions(PayloadInterface $payload): array {
		$records = $payload->getDatum("original-records");
		foreach ($records as $record) {

			// we want to use the abbreviation as the option text for
			// our books, but the title should be the book's name.
			// luckily, our searchbar can handle a JSON string
			// describing that for us.

			$books[$record["book_id"]] = json_encode([
				"text"  => $record["abbreviation"],
				"title" => $record["book"],
			]);
		}

		$books = $books ?? [];
		asort($books);
		return $books;
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
	
	protected function create(): ResponseInterface {
		
		// creation is a two step process:  collect data from the visitor
		// and then save it in the database.  we can tell which step we're
		// on based on the method of this request.
		
		$method = $this->request->getServerVar("REQUEST_METHOD") !== "POST"
			? "getDataToCreate"
			: "createNewRecord";
		
		return $this->{$method}();
	}

	/**
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws ServiceNotFound
	 */
	protected function getDataToCreate(): ResponseInterface {
		
		// when we're creating a new record, what we need from our domain
		// is the schema about our table. there's actually nothing else
		// that it needs from us, so we don't need to send it any data.
		
		$payload = $this->domain->create([
			"recordId" => $this->recordId,
			"table"    => $this->getTable(),
		]);
		
		// now, the only failure is if it could read the table, which
		// probably means the DB is offline.  regardless, we can pass our
		// payload's data to the failure case.  for success, we send back
		// the schema we selected as well as some information
		
		$data = $payload->getData();
		$singular = $this->getSingular();
		
		if ($payload->getSuccess()) {
			$data = array_merge($data, [
				"title"        => "Create New " . ucwords($singular),
				"instructions" => $payload->getDatum("instructions", ""),
				"plural"       => $this->getPlural(),
				"singular"     => $singular,
				"errors"       => "",
			]);
			
			$payload->setData($data);
			$data["form"] = $this->getForm($payload);
			return $this->handleSuccess($data);
		}
		
		// if we weren't successful above, then we must have failed.  we'll
		// send back what our payload sends us and let the Action take over.
		
		return $this->handleFailure($data);
	}
	
	/**
	 * @return string
	 */
	abstract protected function getTable(): string;
	
	/**
	 * @return string
	 */
	abstract protected function getSingular(): string;
	
	/**
	 * @return string
	 */
	abstract protected function getPlural(): string;

	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 * @throws ServiceNotFound
	 */
	protected function getForm(PayloadInterface $payload): string {
		
		// when one of our actions needs to show a form, it can call this
		// method along with a payload that describes the form needed to
		// produce it.
		
		/** @var FormBuilderInterface $formBuilder */
		
		$payloadData = $payload->getData();
		$formBuilder = $this->container->get("FormBuilder");
		$payloadData["currentUrl"] = $this->request->getServerVar("SCRIPT_URL");
		$formBuilder->openForm($payloadData);
		$form = $formBuilder->getForm();
		
		// the $form that we have now, is the actual FormInterface object,
		// but what we want to send as a part of our response is the HTML for
		// it.  therefore, we call the form's getForm() method now, too.
		
		return $form->getForm(false);
	}
	
	/**
	 * @param array $data
	 *
	 * @return ResponseInterface
	 */
	protected function handleSuccess(array $data = []): ResponseInterface {
		
		// each of our handle* methods simply passes control down to the
		// respond method below.  that's the one that does our work, these
		// just tell it how to respond by passing the name of each of our
		// functions to it.  we use __FUNCTION__ instead of __METHOD__
		// because the latter includes the class name and we don't want
		// that.
		
		return $this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param string $function
	 * @param array  $data
	 *
	 * @return ResponseInterface
	 */
	protected function respond(string $function, array $data): ResponseInterface {
		
		// the purpose of this method is to ensure that we always tell the
		// response whether or not we're authentic and to provide it the menu.
		// our authentic state can be gathered from the session object in our
		// request.  the menu is given to us by our Domain.
		
		$data = array_merge($data, [
			"authentic"     => $this->request->getSessionObj()->isAuthenticated() ? 1 : 0,
			"shadowlabMenu" => $this->domain->getShadowlabMenu(),
		]);
		
		// we very specifically named our methods above so that they matched
		// the public methods of our response object.  that way we can use
		// a variable function call as follows to pass our newly authenticated
		// data over to it.  notice we pass our action property over to the
		// response; it's optional, but sometimes they need it.
		
		$this->response->{$function}($data, $this->action);
		return $this->response;
	}
	
	/**
	 * @param array $data
	 *
	 * @return ResponseInterface
	 */
	protected function handleFailure(array $data = []): ResponseInterface {
		return $this->respond(__FUNCTION__, $data);
	}

	/**
	 * @param array|null $post
	 *
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws ServiceNotFound
	 */
	protected function createNewRecord(array $post = null): ResponseInterface {
		if (is_null($post)) {
			$post = $this->request->getPost();
		}

		$payload = $this->domain->create([
			"posted" => $post,
			"idName" => $this->getRecordIdName(),
			"table"  => $this->getTable(),
		]);
		
		// our payload's success is determined by whether or not there
		// were errors discovered within the data posted to us from the
		// visitor.  regardless of whether we're in a success or error
		// state, we can merge in our nouns and send our data back to
		// the client using the appropriate method.
		
		$data = array_merge($payload->getData(), [
			"success"  => $payload->getSuccess(),
			"singular" => $this->getSingular(),
			"plural"   => $this->getPlural(),
		]);
		
		return $payload->getSuccess()
			? $this->handleSuccess($data)
			: $this->handleError(array_merge($data, [
				"title"        => "Unable to Save " . ucwords($this->getSingular()),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
			]));
		
	}
	
	/**
	 * @return string
	 */
	abstract protected function getRecordIdName(): string;
	
	/**
	 * @param array $data
	 *
	 * @return ResponseInterface
	 */
	protected function handleError(array $data = []): ResponseInterface {
		return $this->respond(__FUNCTION__, $data);
	}

	/**
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws ServiceNotFound
	 */
	protected function read(): ResponseInterface {
		$payload = $this->domain->read([
			"recordId" => $this->recordId,
		]);
		
		// a successful read involves sending back a bunch of data to
		// our client.  much of it comes from our payload, but the rest
		// can be gathered either from other methods here or from our
		// request.
		
		if ($payload->getSuccess()) {
			return $this->handleSuccess([
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
		}
		
		// if we didn't return above, then we're a failure.  there's not
		// much to report to the client in this case, but there's a little
		// bit of work to do before we're done.
		
		$noun = $payload->getDatum("count") === 1
			? $this->getSingular()
			: $this->getPlural();
		
		return $this->handleFailure([
			"title" => "Perception Failed",
			"noun"  => $noun,
		]);
	}

	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 * @throws ServiceNotFound
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbarHtml = "";
		
		if ($payload->getDatum("count", 0) > 1) {
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->get("Searchbar");
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
	protected function getCaption(): string {
		
		// most of our collection tables don't need a caption.  so, by
		// default, we'll just return the empty string.  the children of
		// this object can make the changes to it that they need to when
		// they need to.
		
		return "";
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

	/**
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws ServiceNotFound
	 */
	protected function getDataToUpdate(): ResponseInterface {
		$payload = $this->domain->update([
			"recordId" => $this->recordId,
			"table"    => $this->getTable(),
		]);
		
		// when we fail getting data to update, we'll just send whatever
		// the domain tells us back to the client.  otherwise, there's
		// data to help describe the content of our form and information
		// from other methods here.
		
		return !$payload->getSuccess()
			? $this->handleFailure($payload->getData())
			: $this->handleSuccess([
				"title"        => "Edit " . $payload->getDatum("title"),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
				"singular"     => $this->getSingular(),
				"plural"       => $this->getPlural(),
				"errors"       => "",
			]);
	}

	/**
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws ServiceNotFound
	 */
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
			return $this->handleSuccess($data);
		}
		
		// if we encountered errors when validating our data before
		// putting it back into the database, we end up here.  we send
		// back the same information as we do when we first present the
		// form, but
		
		return $this->handleError([
			"title"        => "Unable to Save Changes",
			"posted"       => $payload->getDatum("posted"),
			"errors"       => $payload->getDatum("errors"),
			"form"         => $this->getForm($payload),
			"singular"     => $this->getSingular(),
			"plural"       => $this->getPlural(),
			"instructions" => $this->getErrorInstructions(),
		]);
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
	 * @throws MysqlDomainException
	 * @throws ResponseException
	 */
	protected function delete(): ResponseInterface {
		$payload = $this->domain->delete([
			"recordId" => $this->recordId,
			"idName"   => $this->getRecordIdName(),
			"table"    => $this->getTable(),
		]);
		
		if (!$payload->getSuccess()) {
			return $this->handleFailure($payload->getData());
		}
		
		// when we successfully delete, we just want to re-show the
		// collection.  we can do this with a redirect response as
		// follows.
		
		$host = $this->request->getServerVar("HTTP_HOST");
		$url = $this->request->getServerVar("REQUEST_URI");
		$url = "http://$host" . substr($url, 0, strpos($url, "/delete"));
		$this->response->redirect($url);
		return $this->response;
	}
	
	/**
	 * @param array $data
	 *
	 * @return ResponseInterface
	 */
	protected function handleNotFound(array $data = []): ResponseInterface {
		return $this->respond(__FUNCTION__, $data);
	}
}
