<?php

namespace Shadowlab\Config;

use Aura\Di\ContainerConfig;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ShadowlabContainerConfig
 *
 * this object sits between Aura's container configuration object and the
 * extensions that we've written in ./ContainerConfig for those objects that
 * need access to our list of handlers.  NOTE: this object directly accesses
 * the $_COOKIES super global as we can't ask our Container for an instance
 * of a RequestInterface object without locking it and preventing further
 * configuration changes.
 *
 * @package Shadowlab\Config
 */
class ShadowlabContainerConfig extends ContainerConfig {
	protected const HANDLERS_CACHED = "shadowlab-handlers-cached-on";
	protected const HANDLERS = "shadowlab-handlers";
	protected const EXPIRATION = 2147483647;
	
	protected function getHandlerPath(): string {
		
		// returns the path to our handlers.php file which is located in the
		// same directory as this file.
		
		$path = pathinfo(__FILE__, PATHINFO_DIRNAME);
		return $path . DIRECTORY_SEPARATOR . "handlers.php";
	}
	
	protected function isHandlerCacheValid(string $handlerPath): bool {
		
		// our cache is valid if the date on which our file was last
		// modified is less than or equal to the cached data.  if it's
		// newer than the cache, then we'll want to reload our data
		// from the file.  since it's possible that the cookie doesn't
		// exist yet, we'll use the null coalescing operator set a default
		// of zero.
		
		return filemtime($handlerPath) <= ($_COOKIE[self::HANDLERS_CACHED] ?? 0);
	}
	
	protected function reloadHandlers(string $handlerPath): array {
		
		// when reloading our handlers, we want to require our file which
		// "returns" an array of objects.  that array is what we return.
		// but, before we do that, we also want to cache them so we don't
		// need to redo the work in our handler file again next time.
		
		$handlers = require($handlerPath);
		$this->cacheHandlers($handlers);
		return $handlers;
	}
	
	protected function cacheHandlers(array $handlers): void {
		
		// here we simply set two cookies:  the date on which we created
		// this handler cache and the handlers themselves:
		
		setcookie(self::HANDLERS, serialize($handlers), self::EXPIRATION);
		setcookie(self::HANDLERS_CACHED, time(), self::EXPIRATION);
	}
	
	protected function getHandlerCache() {
		return unserialize($_COOKIE[self::HANDLERS]);
	}
}
