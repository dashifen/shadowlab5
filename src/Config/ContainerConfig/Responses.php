<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Responses extends ContainerConfig {
	public function define(Container $di) {
		
		// for our objects here, we need to know where our wwwroot folder
		// is compared to this one.  the following will build us a path to
		// that folder which we then use to configure our views and our
		// response.
		
		$path = join(DIRECTORY_SEPARATOR, [
			pathinfo(__FILE__, PATHINFO_DIRNAME),	// start in this folder
			'..', 								 	// move up
			'..', 								 	// three folders
			'..', 									// to shadowlab's root
			'wwwroot'								// and down into wwwroot
		]);
		
		$di->params['Shadowlab\View\View']["header"] = join(DIRECTORY_SEPARATOR, [$path, "assets", "layout", "header.php"]);
		$di->params['Shadowlab\View\View']["footer"] = join(DIRECTORY_SEPARATOR, [$path, "assets", "layout", "footer.php"]);
		
		// for our responses, they're all constructed the same; only their
		// implementation is different.  so, we can configure them as follows:
		
		$di->params['Dashifen\Response\AbstractResponse']['view'] = $di->lazyNew('Shadowlab\View\View');
		$di->params['Dashifen\Response\AbstractResponse']['emitter'] = $di->lazyNew('Zend\Diactoros\Response\SapiEmitter');
		$di->params['Dashifen\Response\AbstractResponse']['responseFactory'] = $di->lazyNew('Dashifen\Response\Factory\ResponseFactory');
		
		// the final parameter to our response is the root path for the
		// content that it needs to load.  for this app, that root is the
		// wwwroot/views folder.  we have our path to wwwroot from the work
		// we did above; now we just add our views folder.
		
		$di->params['Dashifen\Response\AbstractResponse']['root_path'] = $path . DIRECTORY_SEPARATOR . "views";;
	}
}
