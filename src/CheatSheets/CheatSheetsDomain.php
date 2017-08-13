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
	 * @return array
	 */
	protected function getRecords(): array {
		return $this->db->getCol("SELECT sheet_type_id FROM sheets_types");
	}
	
	/**
	 * @param int $recordId
	 *
	 * @return PayloadInterface
	 */
	protected function readOne(int $recordId): PayloadInterface {
		$statement = "SELECT sheet_type, sheet_name, route FROM sheets
			INNER JOIN sheets_types USING (sheet_type_id)
			WHERE sheet_type_id = :sheet_type_id
			ORDER BY sheet_name";
		
		// even though we only select one record here, we still call it
		// "records" as we return it so that the AbstractDomain can find
		// what we've selected regardless of whether we select a record
		// or a collection.
		
		$records = $this->db->getResults($statement, ["sheet_type_id" => $recordId]);
		return $this->payloadFactory->newReadPayload(sizeof($records) > 0, [
			"records" => $records
		]);
	}
	
	/**
	 * @return PayloadInterface
	 */
	protected function readAll(): PayloadInterface {
		$records = $this->db->getResults("SELECT sheet_type, sheet_name, route
			FROM sheets INNER JOIN sheets_types USING (sheet_type_id)
			ORDER BY sheet_type, sheet_name");
		
		return $this->payloadFactory->newReadPayload(sizeof($records) > 0, [
			"records" => $records
		]);
	}
	
	/**
	 * @return int
	 */
	protected function getNextId(): int {
		
		// we need to implement this method because it's abstract in our
		// parent.  but, the CheatSheetsDomain doesn't do any updating of
		// information, so it doesn't need a next ID.  we'll just return
		// zero.
		
		return 0;
	}
}
