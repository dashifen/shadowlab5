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
	 * @throws PoolBuilderException
	 */
	public function getPoolId(array $constituents): int;

	/**
	 * @param array $constituents
	 *
	 * @return array
	 */
	public function removePoolConstituents(array $constituents): array;
}