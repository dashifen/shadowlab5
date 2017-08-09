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
	
	public function setContent(string $content): void {
		
		// our parent doesn't give us a way to remember what template we
		// tell our view to use.  so, we'll add that capability here.
		
		$this->contentTemplate = $content;
		parent::setContent($content);
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
	 * @param array  $data
	 * @param string $action
	 *
	 * @return string
	 * @throws ResponseException
	 */
	protected function getTemplate(array $data = [], string $action = "read"): string {
		
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
	
	public function handleSuccess(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Success",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleFailure(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Failure",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleError(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Error",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleNotFound(array $data = [], string $action = "read"): void {
		throw new ResponseException("Unexpected Response: Not Found",
			ResponseException::UNEXPECTED_RESPONSE);
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
}
