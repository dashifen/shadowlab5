<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Shadowlab\Config\ShadowlabContainerConfig;

class Domains extends ShadowlabContainerConfig {
	public function define(Container $di) {
		
		// four out of five parameters to our domains are the same.  so,
		// we'll specify those as a part of the parent class to all of our
		// more specific domains; our Container is smart enough to apply these
		// parameters to its children.
		
		$di->params['Dashifen\Domain\AbstractMysqlDomain']['db'] = $di->lazyGet("database");
		$di->params['Dashifen\Domain\AbstractMysqlDomain']['session'] = $di->lazyGet("session");
		$di->params['Dashifen\Domain\AbstractMysqlDomain']['entityFactory'] = $di->lazyNew('Dashifen\Domain\Entity\Factory\EntityFactory');
		$di->params['Dashifen\Domain\AbstractMysqlDomain']['payloadFactory'] = $di->lazyNew('Dashifen\Domain\Payload\Factory\PayloadFactory');
		
		// because this object extends the Shadowlab's container configuration
		// object, we can use the protected methods of that object to grab the
		// information related to our handler cache and, if necessary, call for
		// the reloading of them from the handlers.php file.
		
		$handlerPath = $this->getHandlerPath();
		$handlers = !$this->isHandlerCacheValid($handlerPath)
			? $this->reloadHandlers($handlerPath)
			: $this->getHandlerCache();
		
		foreach ($handlers as $handler) {
			$di->params[$handler->domain]["validator"] = $di->lazyNew($handler->validator);
			$di->params[$handler->domain]["transformer"] = $di->lazyNew($handler->transformer);
			
			// the domain doesn't need an instance of an entity object, but
			// it does need to know the entity with which it's going to be
			// working.  we can call it's setEntityType method as follows
			// and send it the name of the entity as defined by our handler.
			
			$di->setters[$handler->domain]["setEntityType"] = $handler->entity;
		}
	}
}