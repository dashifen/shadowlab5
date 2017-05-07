<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Shadowlab\Config\ShadowlabContainerConfig;

// notice that this object extends our app's container configuration
// object and not Aura's.  that's because this object needs access to the
// handlers file and our object has a handle method to provide that.

class Actions extends ShadowlabContainerConfig {
	public function define(Container $di) {
		
		// the first parameter to all Action objects is the request.
		// then the second and third parameters are related to the specific
		// actions that we're loading.  Aura/Di can set the parent's parameter
		// and then we can specify the differences for the children.
		
		$di->params['Dashifen\Action\AbstractAction']['request'] = $di->lazyGet("request");
		
		// because this object extends the Shadowlab's container configuration
		// object, we can use the protected methods of that object to grab the
		// information related to our handler cache and, if necessary, call for
		// the reloading of them from the handlers.php file.
		
		$handlerPath = $this->getHandlerPath();
		$handlers = !$this->isHandlerCacheValid($handlerPath)
			? $this->reloadHandlers($handlerPath)
			: $this->getHandlerCache();
		
		foreach ($handlers as $handler) {
			$di->params[$handler->action]['domain'] = $di->lazyNew($handler->domain);
			$di->params[$handler->action]['response'] = $di->lazyNew($handler->response);
		}
	}
}
