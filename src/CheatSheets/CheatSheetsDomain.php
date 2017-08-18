<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractDomain;

class CheatSheetsDomain extends AbstractDomain {
	public function read(array $data = []): PayloadInterface {
		
		// there's actually no way to fail a read for cheat sheets.
		// so, we'll create our payload and then, if we have a sheet
		// type ID, we'll get it's menu and return it as a part of
		// the payload.  otherwise, it'll just use the main menu to
		// show the full list of sheets.
		
		$payload = $this->payloadFactory->newReadPayload(true);
		
		if (is_numeric($data["recordId"])) {
			$sheetTypeMenu = $this->getSheetTypeMenu($data["recordId"]);
			$payload->setDatum("sheetTypeMenu", $sheetTypeMenu);
		}
		
		return $payload;
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
