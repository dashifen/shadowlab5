<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Database extends ContainerConfig {
	public function define(Container $di) {
		$di->set('database', $di->lazyNew('Shadowlab\Framework\Database\Database'));
	}
}
