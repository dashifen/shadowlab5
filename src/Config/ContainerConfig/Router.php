<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Shadowlab\Config\ShadowlabContainerConfig;

// notice that this object extends our app's container configuration
// object and not Aura's.  that's because this object needs access to the
// handlers file and our object has a handle method to provide that.

class Router extends ShadowlabContainerConfig {
	private const ROUTES_MODIFIED = "shadowlab-routes-cached-on";
	private const ROUTES = "shadowlab-routes";
	
	public function define(Container $di) {
		
		// we want to customize the wildcard pattern matching for this
		// app different from the default option within the router package.
		// the following does so:
		
		$di->params['Dashifen\Router\Route\Collection\RouteCollection']['wildcardPattern'] = "`^%s/(?:(create)|(?:(read|update|delete)/)?(\d+)/?)$`";
		
		// the first three parameters for our Router object are fairly straight-
		// forward.  we'll handle them first and then worry about the hard one.
		
		$di->params['Dashifen\Router\Router']['request'] = $di->lazyGet('request');
		$di->params['Dashifen\Router\Router']['collection'] = $di->lazyNew('Dashifen\Router\Route\Collection\RouteCollection');
		$di->params['Dashifen\Router\Router']['factory'] = $di->lazyNew('Dashifen\Router\Route\Factory\RouteFactory');
		
		// now, the last parameter for our Router is the actual list of routes.
		// that list of routes can be found in our handlers file along with the
		// actions that handle them.  we'll use the methods of the Shadowlab's
		// configuration object to get that information here and then process
		// it as we need it below.
		
		$handlerPath = $this->getHandlerPath();
		$handlers = !$this->isHandlerCacheValid($handlerPath)
			? $this->reloadHandlers($handlerPath)
			: $this->getHandlerCache();
		
		$di->params['Dashifen\Router\Router']['routes'] = $this->getRoutes($handlers);
	}
	
	protected function getRoutes(array $handlers): array {
		
		// the handlers array contains a lot of information that we don't
		// need at this time like information about domains and responses
		// and whatnot.  so, here we loop over out handlers and grab only
		// the information about routes and actions for use in our Container's
		// configuration.
		
		$routes = [];
		foreach ($handlers as $handler) {
			
			// the routes property of our $handler objects is an array
			// with either GET or POST indices (or both) defining the
			// routes of within our app.  some routes are prefixed by an
			// exclamation point; these are public, and the rest are
			// private.
			
			foreach ($handler->routes as $method => $route) {
				$public = preg_match("/(?<=!)(.+)/", $route, $matches);

				$routes[] = [
					"method"  => $method,
					"path"    => $public ? $matches[0] : $route,
					"action"  => $handler->action,
					"private" => !$public,
				];
			}
		}
		
		return $routes;
	}
}
