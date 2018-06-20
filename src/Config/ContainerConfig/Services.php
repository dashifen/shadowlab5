<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Aura\Di\Exception\ContainerLocked;
use Aura\Di\Exception\ServiceNotObject;

class Services extends ContainerConfig {
	/**
	 * @param Container $di
	 *
	 * @return null|void
	 * @throws ContainerLocked
	 * @throws ServiceNotObject
	 */
	public function define(Container $di) {
		$di->set('Searchbar', $di->lazyNew('Shadowlab\Framework\AddOns\Searchbar\Searchbar'));
		$di->set('FormBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\FormBuilder\FormBuilder'));
		$di->set('PoolBuilderFactory', $di->lazyNew('Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory\PoolBuilderFactory', ["container" => $di]));
	}
}
