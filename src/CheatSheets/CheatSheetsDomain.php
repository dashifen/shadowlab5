<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractDomain;

class CheatSheetsDomain extends AbstractDomain {
	public function read(array $data = []): PayloadInterface {
		
		// what we get from our Action is not a numeric ID, this time.
		// instead, we get the word that represents a type of sheet in
		// the database.  before we let our parent take over, we need
		// to switch that sheet type to it's linked ID.
		
		$statement = "SELECT sheet_type_id FROM sheets_types WHERE sheet_type = :sheet_type";
		$sheet_type_id = $this->db->getVar($statement, ["sheet_type" => $data["sheet_type"]]);
		return parent::read(["sheet_type_id" => $sheet_type_id]);
	}
	
	/**
	 * @param bool $view
	 *
	 * @return array [string, string, string]
	 */
	protected function getRecordDetails($view = false): array {
		return ["sheet_type_id", "sheets_types", "sheet_type"];
	}
	
	/**
	 * @param int $recordId
	 *
	 * @return array
	 */
	protected function readOne(int $recordId): array {
		$statement = "SELECT sheet_type, sheet_name, route FROM sheets
			INNER JOIN sheets_types USING (sheet_type_id)
			WHERE sheet_type_id = :sheet_type_id
			ORDER BY sheet_name";
		
		return $this->db->getResults($statement, ["sheet_type_id" => $recordId]);
	}
	
	/**
	 * @return array
	 */
	protected function readAll(): array {
		return $this->db->getResults("SELECT sheet_type, sheet_name, route
			FROM sheets INNER JOIN sheets_types USING (sheet_type_id)
			ORDER BY sheet_type, sheet_name");
	}
	
	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return !$isCollection
			? ucwords($records[0]["sheet_type"]) . " Sheets"
			: "Shadowlab Cheat Sheets";
	}
	
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function getNextRecordCriteria(array $record) {
		
		// sheets don't really have a next ID, but we still need a
		// WHERE clause, so we'll return an identity clause.
		
		return ["1=1"];
	}
}
