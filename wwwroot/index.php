<?php
require("../vendor/autoload.php");

use Aura\Di\ContainerBuilder;
use Dashifen\Router\RouterException;

$cb = new ContainerBuilder();
$app = $cb->newConfiguredInstance([
	'Shadowlab\Config\ContainerConfig\Database',
	'Shadowlab\Config\ContainerConfig\Request',
	'Shadowlab\Config\ContainerConfig\Exceptionator',
	'Shadowlab\Config\ContainerConfig\Actions',
	'Shadowlab\Config\ContainerConfig\Domains',
	'Shadowlab\Config\ContainerConfig\Responses',
	'Shadowlab\Config\ContainerConfig\Router',
	'Shadowlab\Config\ContainerConfig\Services',
]);

/**
 * @var \Shadowlab\Framework\Response\ShadowlabResponse $notFoundResponse
 * @var \Dashifen\Exceptionator\Exceptionator           $exceptionator
 * @var \Dashifen\Router\RouterInterface                $router
 * @var \Dashifen\Action\ActionInterface                $action
 */

// first: we set up our exceptionator.  this "converts" PHP errors
// into ErrorExceptions and then handles them in a way that makes them
// print out on the screen in an attractive way.

$exceptionator = $app->newInstance('Dashifen\Exceptionator\Exceptionator');
$exceptionator->handleExceptions(true);
$exceptionator->handleErrors(true);

try {
	
	// in a perfect world, we get our router, let it route us to the
	// appropriate action that we use to handle this request.  execute
	// that action and send its response.
	
	$router = $app->newInstance('Dashifen\Router\Router');
	$route = $router->route();
	
	// now that we've gotten the route that we'll be executing, we can
	// use it to get our action.  and, we'll also pass along an action
	// parameter if there is one for this route.  these are optional and
	// default to the empty string when not in use.
	
	$action = $app->newInstance($route->getAction());
	$response = $action->execute($route->getActionParameter());
	$response->send();
} catch (RouterException $e) {
	
	// but, if we encounter an unexpected route exception, then we
	// want to send a not-found response (i.e. HTTP 404).
	
	if ($e->getCode() == RouterException::UNEXPECTED_ROUTE) {
		
		// the message for this exception is in the form of Unexpected
		// Route: <method>;<path>.  we only need the method/path and not
		// the first stuff, so we'll extract that and send it to our
		// response below.
		
		$message = $e->getMessage();
		$notFoundResponse = $app->newInstance('Shadowlab\Framework\Response\ShadowlabResponse');
		$notFoundResponse->handleNotFound([
			"route" => substr($message, strpos($message, ":") + 1)
		]);
		
		$notFoundResponse->send();
	} else {
		
		// it wasn't an unexpected route that brought us here, we'll
		// rethrow our exception.  this probably means that the app is
		// about to die.
		
		throw $e;
	}
}
