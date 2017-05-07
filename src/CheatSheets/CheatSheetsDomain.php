<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\Domain;

class CheatSheetsDomain extends Domain {
	public function read(array $data = []): PayloadInterface {
		
		// our validator is simply going to check that our sheet type
		// matches the types in the database.  we'll have to get those
		// types here and then pass them to the validator so that it
		// can do its work.
		
		$sheet_types = $this->db->getCol("SELECT sheet_type FROM sheets_types");
		$validation_data = array_merge($data, ["sheet_types" => $sheet_types]);
		if (!$this->validator->validateRead($validation_data)) {
			
			// if we couldn't validate what we were doing, then we have
			// an error.  likely, this is a request for a type of sheet
			// that does not, at this time, exist.  so, we'll tell the
			// visitor all about it.
			
			return $this->payloadFactory->newReadPayload(false);
		}
		
		// if we didn't return above, then we have a valid sheet type.  we'll
		// select the sheets that match this type (or all of them if we don't
		// have a type), and then we can send the results back to the action.
		
		$sheet_type = $data["sheet_type"];
		
		$sheets = !empty($sheet_type)
			? $this->getSheetsByType($sheet_type)
			: $this->getSheets();
		
		// the success of our payload is determined by whether or not
		// we've identified sheets to return.  so if the size of our
		// array is greater than zero, we've been successful.
		
		$success = sizeof($sheets) > 0;
		$payload = $this->payloadFactory->newReadPayload($success, [
			"sheets" => $sheets,
		]);
		
		if ($success) {
			
			// if we were successful, there's some work we have to do to
			// prepare our sheets for display on the screen.  our transformer
			// can take care of that for us.
			
			$payload = $this->transformer->transformRead($payload);
		}
		
		return $payload;
	}
	
	protected function getSheetsByType(string $sheet_type): array {
		$sql = "SELECT sheet_type, sheet_name, route
			FROM sheets INNER JOIN sheets_types USING (sheet_type_id)
			WHERE sheet_type = :sheet_type
			ORDER BY sheet_name";
		
		return $this->db->getResults($sql, ["sheet_type" => $sheet_type]);
	}
	
	protected function getSheets() {
		$sql = "SELECT sheet_type, sheet_name, route
			FROM sheets INNER JOIN sheets_types USING (sheet_type_id)
			ORDER BY sheet_type, sheet_name";
		
		return $this->db->getResults($sql);
	}
}
