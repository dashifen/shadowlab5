<?php

namespace Shadowlab\Config;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Database extends ContainerConfig {
	public function define(Container $di) {
		$di->set('database', $di->lazyNew('Shadowlab\Database\ShadowlabDatabase'));
	}
}
