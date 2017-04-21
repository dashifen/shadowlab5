<?php

namespace Shadowlab\Config;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Zend\Diactoros\ServerRequestFactory;

class Request extends ContainerConfig {
	public function define(Container $di) {
		
		// even though the Request and Session objects are different, they're
		// both considered a part of the request's configuration for the
		// purposes of this application.  therefore, we'll tell our container
		// how to handle both of them here.
		
		// for the session, it needs to be a service and not something we
		// instantiate over and over again.  this is to ensure that it will
		// use the same index for all of this app's session information
		// throughout.
		
		$di->set("session", $di->lazyNew('Dashifen\Session\Session'));
		
		$di->params['Dashifen\Request\Request']['request'] = ServerRequestFactory::fromGlobals();
		$di->params['Dashifen\Request\Request']['session'] = $di->lazyGet('session');
	}
}
