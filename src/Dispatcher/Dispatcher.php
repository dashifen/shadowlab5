<?php

namespace Shadowlab\Dispatcher;

use Interop\Container\ContainerInterface;
use Dashifen\Request\RequestInterface;
use Aura\Router\RouterContainer;

/**
 * Class Dispatcher
 *
 * @package Shadowlab\Dispatcher
 */
class Dispatcher {
	/**
	 * @var ContainerInterface $di;
	 */
	protected $di;
	
	/**
	 * @var RequestInterface $request
	 */
	protected $request;
	
	/**
	 * @var RouterContainer $router;
	 */
	protected $router;
	
	public function __construct(
		ContainerInterface $di,
		RequestInterface $request,
		RouterContainer $router
	) {
		$this->di = $di;
		$this->request = $request;
		$this->router = $router;
	}
	
	public function dispatch(): void {
	
	}
}
