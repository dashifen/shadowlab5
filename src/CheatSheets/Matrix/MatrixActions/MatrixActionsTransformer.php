<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractTransformer;
use Dashifen\Database\DatabaseException;

class MatrixActionsTransformer extends AbstractTransformer {
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformCreate(PayloadInterface $payload): PayloadInterface {

		// this one is a bit of a bear because of the link between the
		// matrix actions and pools table.  before we can insert/update
		// the matrix actions table, we need to either create or find
		// the pools specified within our record.

		$record["offensive_pool_id"] = $this->getOffensivePoolId($payload);
		$record["defensive_pool_id"] = $this->getDefensivePoolId($payload);



		return parent::transformCreate($payload);
	}

	/**
	 * @param array $record
	 *
	 * @return int
	 * @throws DatabaseException
	 */
	protected function getOffensivePoolId(array $record): int {
		$data = [
			"skill_id"     => $record["offensive_skill_id"],
			"attribute_id" => $record["offensive_attribute_id"],
			"limit_id"     => $record["offensive_limit_id"],
		];

		return $this->getPoolId($data);
	}

	/**
	 * @param array $data
	 *
	 * @return int
	 * @throws DatabaseException
	 */
	protected function getPoolId(array $data): int {
		$sql = $this->getStatement($data);

		// here we try to select a previously inserted pool using
		// the $data that identifies it.  if we can't get one from
		// the database, then we use that same information to make
		// a new one and return its ID.

		$poolId = $this->db->getVar($sql, $data);

		return !is_numeric($poolId)
			? $this->db->insert("pools", $data)
			: $poolId;
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	protected function getStatement(array $data): string {
		$statement = "SELECT pool_id FROM pools";

		$clauses = [];
		foreach (array_keys($data) as $column) {
			$clauses[] = "$column = :$column ";
		}

		return $statement . " WHERE " . join(" AND ", $clauses);
	}

	/**
	 * @param array $record
	 *
	 * @return int
	 * @throws DatabaseException
	 */
	protected function getDefensivePoolId(array $record): int {
		$data = [
			"attribute_id"  => $record["defensive_attribute_id"],
			"other_attr_id" => $record["defensive_other_attr_id"],
		];

		return $this->getPoolId($data);
	}
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {

		// for this one, oddly, there's no need for abbreviations.  all of
		// the header columns are good just the way they are.

		return "";
	}
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderClasses(string $header, array $records): string {
		$classes = "";
		
		switch ($header) {
			case "marks":
			case "actions":
				$classes = "w5 text-center";
				break;
				
			case "matrix_action":
				$classes = "w20";
				break;
		}
		
		return $classes;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getSearchbarValue(string $column, string $value, array $record): string {
		$sbValue = "";
		
		switch ($column) {
			case "matrix_action":
				$sbValue = strip_tags($column);
				break;
				
			case "marks":
			case "action":
				$sbValue = $value;
				break;
		}
		
		return $sbValue;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {

		// the only alteration we need to do here is to our action's name.  we
		// want to make it a clicker for the display toggling behavior for
		// our descriptive row as follows:

		return $column === "matrix_action"
			? sprintf('<a href="#">%s</a>', $value)
			: $value;
	}
	
}
