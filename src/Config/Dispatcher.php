<?php

namespace Shadowlab\Config;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Dispatcher extends ContainerConfig {
	public function define(Container $di) {
		$di->params['Shadowlab\Dispatcher\Dispatcher']['di'] = $di;
		$di->params['Shadowlab\Dispatcher\Dispatcher']['request'] = $di->lazyNew('Dashifen\Request\Request');
		$di->params['Shadowlab\Dispatcher\Dispatcher']['router'] = $di->lazyNew('Dashifen\Router\Router');
	}
}
