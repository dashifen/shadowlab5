<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

/**
 * Interface PoolBuilderInterface
 * @package Shadowlab\Framework\AddOns\PoolBuilder
 */
interface PoolBuilderInterface {
	/**
	 * @param array $constituents
	 *
	 * @return int
	 */
	public function getPoolId(array $constituents): int;
}