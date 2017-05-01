<?php
require("../vendor/autoload.php");

use Aura\Di\ContainerBuilder;

$cb = new ContainerBuilder();
$app = $cb->newConfiguredInstance([
	'Shadowlab\Config\ContainerConfig\Database',
	'Shadowlab\Config\ContainerConfig\Request',
	'Shadowlab\Config\ContainerConfig\Exceptionator',
	'Shadowlab\Config\ContainerConfig\Actions',
	'Shadowlab\Config\ContainerConfig\Domains',
	'Shadowlab\Config\ContainerConfig\Responses',
	'Shadowlab\Config\ContainerConfig\Router',
]);

// now that we've configured our application, we want to get our router.
// it's the object that handles the identification what action handles the
// current request.  it's route() method (as in the verb, to route, not
// the noun) uses it's internal list of the routes within the app (configured
// above) to do so.  then, we'll also ask our app to get us an instance of
// that action which we can then execute.

/**
 * @var \Dashifen\Exceptionator\Exceptionator $exceptionator
 * @var \Dashifen\Router\RouterInterface $router
 * @var \Dashifen\Action\ActionInterface $action
 */

$exceptionator = $app->newInstance('Dashifen\Exceptionator\Exceptionator');
$exceptionator->handleExceptions(true);
$exceptionator->handleErrors(true);

$router = $app->newInstance('Dashifen\Router\Router');
$action = $app->newInstance($router->route());
$response = $action->execute();
$response->send();
