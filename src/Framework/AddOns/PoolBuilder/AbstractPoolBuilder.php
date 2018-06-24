<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;
use Dashifen\Database\DatabaseException;
use Dashifen\Database\DatabaseInterface;

/**
 * Class PoolBuilder
 * @package Shadowlab\Framework\AddOns\PoolBuilder
 */
abstract class AbstractPoolBuilder implements PoolBuilderInterface {
	protected const CONSTITUENTS = [];

	/**
	 * @var DatabaseInterface $db
	 */
	protected $db;

	/**
	 * PoolBuilder constructor.
	 *
	 * @param DatabaseInterface $db
	 */
	public function __construct(DatabaseInterface $db) {
		$this->db = $db;
	}

	/**
	 * @param array $constituents
	 *
	 * @return int
	 * @throws PoolBuilderException
	 * @throws DatabaseException
	 */
	public function getPoolId(array $constituents): int {
		if ($this->canGetPoolId($constituents)) {

			// once we know that we can get a pool ID for these
			// $constituents, we need to see if it's already in the
			// database or if we need to make it.  the name of the
			// $constituents that we get here is unlikely to be the
			// same as the database columns, so we'll transform it
			// first.  then, with the transformed set of constituents,
			// we can build a SQL statement to select the pool ID for
			// them.  if we find it, we're done.  otherwise, we insert
			// it.

			$transformedConstituents = $this->transformConstituents($constituents);

			if (sizeof($transformedConstituents) > 0) {
				$statement = $this->getPoolSelect($transformedConstituents);
				$poolId = $this->db->getVar($statement, $transformedConstituents);

				if (!is_numeric($poolId)) {
					$poolId = $this->db->insert("pools", $transformedConstituents);
				}

				return $poolId;
			}
		}

		// down here, we didn't have some of our necessary constituents to
		// identify or insert this pool.  so, we'll throw an exception.

		throw new PoolBuilderException(
			$this->getMissingConstituentsMessage(),
			$this->getMissingConstituentsErrorNumber()
		);
	}

	/**
	 * @param array $constituents
	 *
	 * @return bool
	 */
	protected function canGetPoolId(array $constituents): bool {

		// we want to see if all of our needles are in our haystack.  to
		// do that, we get the intersection of our arrays.  then, if the
		// size of that intersection is the same as the size of our needles,
		// we're good to go.

		$haystack = array_keys($constituents);
		$needles = array_keys(static::CONSTITUENTS);
		$intersection = array_intersect($needles, $haystack);
		return sizeof($intersection) === sizeof($needles);
	}

	/**
	 * @param array $constituents
	 *
	 * @return array
	 */
	protected function transformConstituents(array $constituents): array {
		$transformed = [];

		// we'll loop over the late static binding of our constant using
		// it's keys to identify the parts of $constituents that we need
		// after our transformation.

		foreach (static::CONSTITUENTS as $constituentKey => $column) {
			$transformed[$column] = $constituents[$constituentKey];
		}

		return $transformed;
	}

	/**
	 * @param array $constituents
	 *
	 * @return string
	 */
	protected function getPoolSelect(array $constituents) {
		$statement = "SELECT pool_id FROM pools";

		// all of our statements begin the same way, but they usually have
		// different WHERE clauses.  the clauses we need can be determined
		// from the keys of $constituents.

		$clauses = [];
		foreach (array_keys($constituents) as $column) {

			// the keys of our constituents are the names of columns in
			// the pools table.  so, we can add them to our array of WHERE
			// clauses here, and then we join them with AND operators
			// below.

			$clauses[] = "$column = :$column ";
		}

		return $statement . " WHERE " . join(" AND ", $clauses);
	}

	/**
	 * @param array $constituents
	 *
	 * @return array
	 */
	public function removePoolComponents(array $constituents): array {
		foreach(array_keys(static::CONSTITUENTS) as $constituentKey) {
			unset($constituents[$constituentKey]);
		}

		return $constituents;
	}


	/**
	 * @return string
	 */
	abstract function getMissingConstituentsMessage(): string;

	/**
	 * @return int
	 */
	abstract function getMissingConstituentsErrorNumber(): int;
}