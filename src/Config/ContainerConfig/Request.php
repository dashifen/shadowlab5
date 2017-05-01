<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Zend\Diactoros\ServerRequestFactory;

class Request extends ContainerConfig {
	public function define(Container $di) {
		
		// even though the Request and Session objects are different, they're
		// both considered a part of the request's configuration for the
		// purposes of this application.  therefore, we'll tell our container
		// how to handle both of them here.
		
		// for the session, we're going to use it as a service because there's
		// only one session so there only needs to be one session object.  it
		// would work as a "normal" object getting instantiated and injected
		// normally, but this feels more appropriate considering what a session
		// is.
		
		$di->set("session", $di->lazyNew('Dashifen\Session\Session'));
		
		$di->params['Dashifen\Request\Request']['request'] = ServerRequestFactory::fromGlobals();
		$di->params['Dashifen\Request\Request']['session'] = $di->lazyGet('session');
		
		// to help save a little bit of time, we'll create a service for our
		// request object, too.  this helps to avoid the need to call the above
		// static method over and over again for things that need access to the
		// PHP super globals.
		
		$di->set("request", $di->lazyNew('Dashifen\Request\Request'));
	}
}
