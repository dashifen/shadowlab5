<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory;

use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderInterface;

/**
 * Class PoolBuilderFactory
 * @package Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory
 */
class PoolBuilderFactory implements PoolBuilderFactoryInterface {
	/**
	 * @var bool
	 */
	protected $validStrategy = true;

	/**
	 * @var bool
	 */
	protected $validConstituents = true;

	/**
	 * @param int $strategy
	 * @param int $constituents
	 *
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getPoolBuilder(int $strategy, int $constituents): PoolBuilderInterface {
		$this->validStrategy = $this->isValidStrategy($strategy);
		$this->validConstituents = $this->isValidConstituents($constituents);
		if ($this->validStrategy && $this->validConstituents) {

			// if we have a valid strategy and constituent value, then we
			// can use them to identify the pool that we want to build.  then,
			// we instantiate that builder and return it to the calling scope.

			$poolBuilder = $this->identifyPoolBuilderClassName($strategy, $constituents);
			return new $poolBuilder();
		} else {
			throw new $this->getException();
		}
	}

	/**
	 * @param int $strategy
	 *
	 * @return bool
	 */
	protected function isValidStrategy(int $strategy): bool {
		return $strategy === self::OFFENSIVE || $strategy === self::DEFENSIVE;
	}

	/**
	 * @param int $constituents
	 *
	 * @return bool
	 */
	protected function isValidConstituents(int $constituents): bool {
		return $constituents === self::ATTRIBUTE_AND_SKILL || $constituents === self::ATTRIBUTE_ONLY;
	}

	/**
	 * @param int $strategy
	 * @param int $constituents
	 *
	 * @return string
	 * @throws PoolBuilderFactoryException
	 */
	protected function identifyPoolBuilderClassName(int $strategy, int $constituents): string {

		// to identify the pool builder class name that we need, we'll add
		// our parameters.  the sum for valid comibnations of strategy and
		// constituent are all different, armed with those sums, we can return
		// the right name.

		switch ($strategy + $constituents) {
			case (self::OFFENSIVE + self::ATTRIBUTE_ONLY):
				return "OffensiveAttrOnlyPoolBuilder";

			case (self::OFFENSIVE + self::ATTRIBUTE_AND_SKILL):
				return "OffensiveAttrSkillPoolBuilder";

			case (self::DEFENSIVE + self::ATTRIBUTE_ONLY):
				return "DefensiveAttrOnlyPoolBuilder";

			case (self::DEFENSIVE + self::ATTRIBUTE_AND_SKILL):
				return "DefensiveAttrSkillPoolBuilder";
		}

		throw new PoolBuilderFactoryException("Unknown pool builder factory error.");
	}

	/**
	 * @return PoolBuilderFactoryException
	 */
	protected function getException(): PoolBuilderFactoryException {
		return new PoolBuilderFactoryException($this->getMessage(), $this->getExceptionCode());
	}

	/**
	 * @return string
	 */
	protected function getMessage(): string {
		if (!$this->validStrategy) {
			return "Invalid pool strategy.";
		}

		if ($this->validConstituents) {
			return "Invalid pool constituents.";
		}

		return "Invalid pool strategy and constituents.";
	}

	/**
	 * @return int
	 */
	protected function getExceptionCode(): int {
		if (!$this->validStrategy) {
			return PoolBuilderFactoryException::INVALID_STRATEGY;
		}

		if ($this->validConstituents) {
			return PoolBuilderFactoryException::INVALID_CONSTITUENTS;
		}

		return PoolBuilderFactoryException::INVALID_BOTH;
	}
}
