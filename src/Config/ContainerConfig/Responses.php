<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Responses extends ContainerConfig {
	public function define(Container $di) {
		
		// for our objects here, we need to know where our views folder
		// is compared to this one.  the following will build us a path to
		// that folder which we then use to configure our views and our
		// response.
		
		$path = join(DIRECTORY_SEPARATOR, [
			pathinfo(__FILE__, PATHINFO_DIRNAME),	// start in this folder
			'..', 								 	// move up
			'..', 								 	// three folders
			'..', 									// to shadowlab's root
			'views'									// and down into views
		]);
		
		$di->params['Shadowlab\Framework\View\View']["header"] = join(DIRECTORY_SEPARATOR, [$path, "__layout", "header.html"]);
		$di->params['Shadowlab\Framework\View\View']["footer"] = join(DIRECTORY_SEPARATOR, [$path, "__layout", "footer.html"]);
		
		// for our responses, they're all constructed the same; only their
		// implementation is different.  so, we can configure them as follows:
		
		$di->params['Shadowlab\Framework\Response\AbstractResponse']['view'] = $di->lazyNew('Shadowlab\Framework\View\View');
		$di->params['Shadowlab\Framework\Response\AbstractResponse']['emitter'] = $di->lazyNew('Zend\Diactoros\Response\SapiEmitter');
		$di->params['Shadowlab\Framework\Response\AbstractResponse']['responseFactory'] = $di->lazyNew('Dashifen\Response\Factory\ResponseFactory');
		
		// the final parameter to our response is the root path for the
		// content that it needs to load.  that root is the same views folder
		// we identified above.  we can pass that along as follows:
		
		$di->params['Shadowlab\Framework\Response\AbstractResponse']['root_path'] = $path;
	}
}
