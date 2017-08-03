<?php

namespace Shadowlab\Config\ContainerConfig;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Services extends ContainerConfig {
	public function define(Container $di) {
		$di->set('searchbar', $di->lazyNew('Shadowlab\Framework\AddOns\Searchbar'));
		$di->set('formBuilder', $di->lazyNew('Dashifen\Form\Builder\FormBuilder'));
	}
}
