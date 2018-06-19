<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory;

use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderInterface;

/**
 * Interface PoolBuilderFactoryInterface
 * @package Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory
 */
interface PoolBuilderFactoryInterface {
	public const OFFENSIVE = 1;
	public const DEFENSIVE = 2;
	public const ATTRIBUTE_AND_SKILL = 4;
	public const ATTRIBUTE_ONLY = 8;

	/**
	 * Given a strategy and a type using the constants above, returns a
	 * PoolBuilderInterface object that can build the necessary pool.
	 *
	 * @param int $strategy
	 * @param int $constituents
	 *
	 * @return mixed
	 * @throws PoolBuilderFactoryException
	 */
	public function getPoolBuilder(int $strategy, int $constituents): PoolBuilderInterface;
}