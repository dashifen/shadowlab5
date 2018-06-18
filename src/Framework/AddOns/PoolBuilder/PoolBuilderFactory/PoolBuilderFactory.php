<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory;

use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderInterface;

/**
 * Class PoolBuilderFactory
 * @package Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory
 */
class PoolBuilderFactory implements PoolBuilderFactoryInterface {
	/**
	 * @param int $strategy
	 * @param int $constituents
	 *
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getPoolBuilder(int $strategy, int $constituents): PoolBuilderInterface {
		$validStrategy = $this->validStrategy($strategy);
		$validConstituents = $this->validConstituents($constituents);

		if ($validStrategy && $validConstituents) {

		} else {
			if (!$validStrategy) {
				throw new PoolBuilderFactoryException("Invalid pool strategy.", PoolBuilderFactoryException::INVALID_STRATEGY);
			} elseif (!$validConstituents) {
				throw new PoolBuilderFactoryException("Invalid pool constituents", PoolBuilderFactoryException::INVALID_CONSTITUENTS);
			} else {
				throw new PoolBuilderFactoryException("Invalid pool strategy and constituents.", PoolBuilderFactoryException::INVALID_BOTH);
			}
		}
	}

	/**
	 * @param int $strategy
	 *
	 * @return bool
	 */
	protected function validStrategy(int $strategy): bool {
		return $strategy === self::OFFENSIVE || $strategy === self::DEFENSIVE;
	}

	protected function validConstituents(int $constituents): bool {
		return $constituents === self::ATTRIBUTE_AND_SKILL || $constituents === self::ATTRIBUTE_ONLY;
	}
}
