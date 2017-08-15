1<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Shadowlab\Framework\Domain\AbstractDomain;

class AdeptPowersDomain extends AbstractDomain {
	/**
	 * when we getRecords(), we want a list of the records against which
	 * we test ID numbers to determine whether or not we can read information
	 * from the database.
	 *
	 * @return array
	 */
	protected function getRecords(): array {
		return $this->db->getCol("SELECT adept_power_id FROM adept_powers");
	}
	
	/**
	 * this method simply grabs a single record from the database based
	 * on the ID number.
	 *
	 * @param int $recordId
	 *
	 * @return array
	 */
	protected function readOne(int $recordId): array {
	
	}
	
	/**
	 * this one gets all records in a collection from the database and
	 * returns them all at once.
	 *
	 * @return array
	 */
	protected function readAll(): array {
		// TODO: Implement readAll() method.
	}
	
	/**
	 * this one simply returns the title for our records (e.g. spells or
	 * qualities or vehicles).  at this level, we don't know what we're
	 * working with, but
	 *
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		// TODO: Implement getRecordsTitle() method.
	}
	
	/**
	 * for the purposes of quickly identifying where to start updating
	 * the database with new information, this one gets the next ID
	 * number from the database
	 *
	 * @return int
	 */
	protected function getNextId(): int {
		// TODO: Implement getNextId() method.
	}
	
}
