<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory;

use Shadowlab\Framework\AddOns\PoolBuilder\OffensiveAttrOnlyPoolBuilder;
use Shadowlab\Framework\AddOns\PoolBuilder\OffensiveAttrSkillPoolBuilder;
use Shadowlab\Framework\AddOns\PoolBuilder\DefensiveAttrOnlyPoolBuilder;
use Shadowlab\Framework\AddOns\PoolBuilder\DefensiveAttrSkillPoolBuilder;
use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderInterface;
use Shadowlab\Framework\Database\Database;

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
	 * @var Database
	 */
	protected $database;

	/**
	 * PoolBuilderFactory constructor.
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}

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
			// note that we have

			return $this->identifyPoolBuilderClassName($strategy, $constituents);
		} else {
			throw $this->getException();
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
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	protected function identifyPoolBuilderClassName(int $strategy, int $constituents): PoolBuilderInterface {

		// to identify the pool builder class name that we need, we'll add
		// our parameters.  the sum for valid combinations of strategy and
		// constituent are all different, armed with those sums, we can return
		// the right name.

		switch ($strategy + $constituents) {
			case (self::OFFENSIVE + self::ATTRIBUTE_ONLY):
				return new OffensiveAttrOnlyPoolBuilder($this->database);

			case (self::OFFENSIVE + self::ATTRIBUTE_AND_SKILL):
				return new OffensiveAttrSkillPoolBuilder($this->database);

			case (self::DEFENSIVE + self::ATTRIBUTE_ONLY):
				return new DefensiveAttrOnlyPoolBuilder($this->database);

			case (self::DEFENSIVE + self::ATTRIBUTE_AND_SKILL):
				return new DefensiveAttrSkillPoolBuilder($this->database);
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
		if (!$this->validConstituents && !$this->validStrategy) {
			return "Invalid pool strategy and constituents.";
		}

		if (!$this->validStrategy) {
			return "Invalid pool strategy.";
		}

		return "Invalid pool constituents.";
	}

	/**
	 * @return int
	 */
	protected function getExceptionCode(): int {
		if (!$this->validConstituents && !$this->validStrategy) {
			return PoolBuilderFactoryException::INVALID_BOTH;
		}

		if (!$this->validStrategy) {
			return PoolBuilderFactoryException::INVALID_STRATEGY;
		}

		return PoolBuilderFactoryException::INVALID_CONSTITUENTS;
	}

	/**
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getOffensiveAttrOnlyPoolBuilder(): PoolBuilderInterface {
		return $this->getPoolBuilder(self::OFFENSIVE, self::ATTRIBUTE_ONLY);
	}

	/**
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getOffensiveAttrSkillPoolBuilder(): PoolBuilderInterface {
		return $this->getPoolBuilder(self::OFFENSIVE, self::ATTRIBUTE_AND_SKILL);
	}

	/**
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getDefensiveAttrOnlyPoolBuilder(): PoolBuilderInterface {
		return $this->getPoolBuilder(self::DEFENSIVE, self::ATTRIBUTE_ONLY);
	}

	/**
	 * @return PoolBuilderInterface
	 * @throws PoolBuilderFactoryException
	 */
	public function getDefensiveAttrSkillPoolBuilder(): PoolBuilderInterface {
		return $this->getPoolBuilder(self::DEFENSIVE, self::ATTRIBUTE_AND_SKILL);
	}
}
