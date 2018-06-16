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
		$di->set('searchbar', $di->lazyNew('Shadowlab\Framework\AddOns\Searchbar\Searchbar'));
		$di->set('formBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\FormBuilder\FormBuilder'));

		$this->addPoolBuilders($di);
	}

	/**
	 * @param Container $di
	 *
	 * @return void
	 * @throws ContainerLocked
	 * @throws ServiceNotObject
	 */
	protected function addPoolBuilders(Container $di): void {
		$di->params['Shadowlab\Framework\AddOns\PoolBuilder\AbstractPoolBuilder']['db'] = $di->lazyGet('database');
		$di->set('offensiveAttrOnlyPoolBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\PoolBuilder\OffensiveAttrOnlyPoolBuilder'));
		$di->set('offensiveAttrSkillPoolBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\PoolBuilder\OffensiveAttrSkillPoolBuilder'));
		$di->set('defensiveAttrOnlyPoolBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\PoolBuilder\DefensiveAttrOnlyPoolBuilder'));
		$di->set('defensiveAttrSkillPoolBuilder', $di->lazyNew('Shadowlab\Framework\AddOns\PoolBuilder\DefensiveAttrSkillPoolBuilder'));
	}

}
