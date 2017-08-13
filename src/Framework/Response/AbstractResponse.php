<?php

namespace Shadowlab\Framework\Response;

use Dashifen\Response\AbstractResponse as DashifenAbstractResponse;
use Dashifen\Response\Factory\ResponseFactoryInterface;
use Dashifen\Response\ResponseException;
use Dashifen\Response\View\ViewInterface;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Class Response
 *
 * implements our four abstract response methods to throw exceptions.
 * children of this object have to override those methods which should
 * not do so.
 *
 * @package Shadowlab\Framework\Response
 */
abstract class AbstractResponse extends DashifenAbstractResponse {
	
	/**
	 * @var string
	 */
	protected $responseType = "";
	
	/**
	 * @var string
	 */
	protected $contentTemplate = "";
	
	public function __construct(
		ViewInterface $view,
		EmitterInterface $emitter,
		ResponseFactoryInterface $responseFactory,
		$root_path = ""
	) {
		parent::__construct($view, $emitter, $responseFactory, $root_path);
		
		// most of our views have a place to put an error.  rather than
		// having to remember to put it in there all the time, we'll assume
		// that there isn't an error until we find out that there is one.
		
		$this->setDatum("error", "");
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$this->setResponseType("success");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param string $responseType
	 *
	 * @throws ResponseException
	 */
	protected function setResponseType(string $responseType): void {
		$responseType = strtolower($responseType);
		
		if (!in_array($responseType, ["success", "failure", "error", "notfound"])) {
			throw new ResponseException("Unexpected response type: $responseType");
		}
		
		$this->responseType = $responseType;
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	protected function setTemplate(array $data = [], string $action = "read"): void {
		
		// by default, to set our template, we use our $data and $action
		// parameters to identify it.  then, we can set it as this response's
		// content, and specify the data that goes into it.
		
		$template = $this->getTemplate($data, $action);
		$this->setContent($template);
		$this->setData($data);
	}
	
	protected function getTemplate(array $data = [], string $action = "read"): string {
		
		// if we can identify an HTTP Error template, we use it.  otherwise,
		// we let the switch statement below take over to identify a more
		// specific template.
		
		if (!empty(($httpErrorTemplate = $this->getHttpErrorTemplate($data)))) {
			return $httpErrorTemplate;
		}
		
		// now, if we have a response type, it must be in the approved list
		// because the setResponseType() method confirms it.  but, if we don't
		// even have a responseType, that's a problem.
		
		if (empty($this->responseType)) {
			throw new ResponseException("Response Type Required");
		}
		
		$template = "";
		switch ($this->responseType) {
			case "success":
				
				// the exact template we use for successful responses
				// changes based on what we're doing.  since this case
				// is more complex, we're going to handle it in its own
				// method below.
				
				$template = $this->getSuccessTemplate($data, $action);
				break;
			
			case "error":
				$template = "update/form.html";
				break;
			
			case "failure":
			case "notfound":
				$template = "not-found/record.php";
				break;
		}
		
		return $this->getHandlerTemplate($template);
	}
	
	/**
	 * @param array $data
	 *
	 * @return string
	 * @throws ResponseException
	 */
	protected function getHttpErrorTemplate(array $data = []): string {
		
		// the purpose of this getTemplate() method is to look for HTTP errors.
		// if $data has a key for one, then we can do all the necessary work
		// to respond to our request here.
		
		if (!isset($data["httpError"])) {
			return "";
		}
		
		// we're going to play a little fast and loose with the single
		// responsibility principle.  technically, getting our template is
		// what we're supposed to be doing.  but, it'd also be nice if we
		// could set our statusCode here, too.
		
		$phrase = $data["httpError"];
		$statusCode = $this->getStatusCode($phrase);
		if ($statusCode === -1) {
			
			// if we don't have a valid phrase, then we'll just default to
			// a 406 (Bad Request) error.  it's probably not the best code,
			// but it's the closest thing we have to an unknown error that
			// is available to us.  plus, something probably was wrong with
			// the request or we wouldn't be here!
			
			$statusCode = 400;
		}
		
		$this->setStatusCode($statusCode);
		
		// and, here's where we tell the calling scope that we've found
		// our template.
		
		return "error.html";
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return string
	 */
	protected function getSuccessTemplate(array $data = [], string $action = "read"): string {
		$template = "not-found/record.html";
		
		switch ($action) {
			case "read":
				
				// when we're reading information, the template we use is
				// based on the number of items we've selected from the
				// database.  more than one and we should a collection of
				// items; otherwise, a single one.
				
				$template = isset($data["count"]) && $data["count"] > 1
					? "read/collection.html"
					: "read/single.html";
				
				break;
			
			case "update":
				
				// when updating, if we have a record of our success, then
				// we'll want to share that success with our visitor.
				// otherwise, we give them the form so they can enter data
				// we use to perform our update.
				
				$template = isset($data["success"]) && $data["success"]
					? "update/success.html"
					: "update/form.html";
		}
		
		return $this->getHandlerSuccessTemplate($template);
	}
	
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	abstract protected function getHandlerSuccessTemplate(string $template): string;
	
	/**
	 * @param string $template
	 *
	 * @return string
	 */
	abstract protected function getHandlerTemplate(string $template): string;
	
	public function setContent(string $content): void {
		
		// our parent doesn't give us a way to remember what template we
		// tell our view to use.  so, we'll add that capability here.
		
		$this->contentTemplate = $content;
		parent::setContent($content);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleFailure(array $data = [], string $action = "read"): void {
		$this->setResponseType("failure");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleError(array $data = [], string $action = "read"): void {
		$this->setResponseType("error");
		$this->setTemplate($data, $action);
	}
	
	/**
	 * @param array  $data
	 * @param string $action
	 *
	 * @return void
	 */
	public function handleNotFound(array $data = [], string $action = "read"): void {
		
		// in addition to whatever data the calling scope sent us,
		// we want to make sure to set the following so that they'll
		// be correct when the page loads.
		
		$data = array_merge($data, [
			"title"     => "Not Found",
			"heading"   => "Critical Glitch",
			"httpError" => "Not Found",
		]);
		
		$this->setResponseType("notfound");
		$this->setTemplate($data, $action);
	}
	
	public function send(): void {
		
		// before we send this response, if we're printing our error
		// message, we want to be sure we have a message to print.  we'll
		// check for that now.
		
		$isError = $this->contentTemplate === "error.html";
		$withMessage = isset($this->data["httpErrorMessage"]);
		if ($isError && !$withMessage) {
			
			// if we need to set our httpErrorMessage, then we do so here.
			// but, it's useless to set it in our data property because the
			// view won't get it at this point.  so, we'll send it directly
			// to the view instead.
			
			$this->view->setDatum("httpErrorMessage", $this->getErrorMessage());
		}
		
		// and, now we can let the parent take over from here.
		
		parent::send();
	}
	
	/**
	 * @return string
	 */
	protected function getErrorMessage(): string {
		switch ($this->statusCode) {
			case 401:
				return "<p>You do not have enough marks on the target to
					perform this action.  If you think you should be able to do
					so, contact Dash and he'll look into things.  Just let him
					know what you were trying to do so he knows where to
					look.</p>";
			
			case 403:
				return "<p>Unfortunately, you have exceeded the number of
					failed login attempts that this application allows.  GOD
					has been notified; expect convergence in 3&hellip;
					2&hellip; 1&hellip;</p><p>Please restart your browser and
					then return to the Shadowlab to try and log in again.</p>";
			
			case 404:
				return "<p>The Shadowlab has critically glitched; the paydata
					you requested could not be found.</p>";
			
			default:
				return "<p>An unknown error occurred.  Try again and, if it
					persists, contact Dash because he clearly messed something
					up.</p>";
		}
	}
	
}
