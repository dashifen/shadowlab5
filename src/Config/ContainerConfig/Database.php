<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Aura\Di\Exception\ContainerLocked;
use Aura\Di\Exception\ServiceNotObject;

/**
 * Class Database
 * @package Shadowlab\Config\ContainerConfig
 */
class Database extends ContainerConfig {
	/**
	 * @param Container $di
	 *
	 * @return void
	 * @throws ContainerLocked
	 * @throws ServiceNotObject
	 */
	public function define(Container $di): void {
		$di->set('database', $di->lazyNew('Shadowlab\Framework\Database\Database'));
	}
}
