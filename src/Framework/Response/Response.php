<?php

namespace Shadowlab\Framework\Response;

use Dashifen\Response\AbstractResponse;
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
class Response extends AbstractResponse {
	public function __construct(ViewInterface $view, EmitterInterface $emitter, ResponseFactoryInterface $responseFactory, $root_path = "") {
		parent::__construct($view, $emitter, $responseFactory, $root_path);
		
		// most of our views have a place to put an error.  rather than
		// having to remember to put it in there all the time, we'll assume
		// that there isn't an error until we find out that there is one.
		
		$this->setDatum("error", "");
	}
	
	
	public function handleSuccess(array $data = []): void {
		throw new ResponseException("Unexpected Response: Success",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleFailure(array $data = []): void {
		throw new ResponseException("Unexpected Response: Failure",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleError(array $data = []): void {
		throw new ResponseException("Unexpected Response: Error",
			ResponseException::UNEXPECTED_RESPONSE);
	}
	
	public function handleNotFound(array $data = []): void {
		throw new ResponseException("Unexpected Response: Not Found",
			ResponseException::UNEXPECTED_RESPONSE);
	}
}
