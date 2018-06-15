<?php

namespace Shadowlab\CheatSheets\Matrix\Programs;

use Shadowlab\Framework\Domain\AbstractDomain;
use Dashifen\Database\DatabaseException;

class ProgramsDomain extends AbstractDomain {
	/**
	 * @param bool $view
	 *
	 * @return array
	 */
	protected function getRecordDetails($view = false): array {

		// our parent knows how to get information out of the database; this
		// tells it what and where to get those data as well as what to order
		// it by.  thus, we get programs identified by program ID out of the
		// programs table ordered by the program's name.

		return ["program_id", "programs", "program"];
	}

	/**
	 * @param int $recordId
	 *
	 * @return array
	 * @throws DatabaseException
	 */
	protected function readOne(int $recordId): array {
		$sql = $this->getQuery() . " WHERE program_id = :program_id";
		return $this->db->getRow($sql, ["progarm_id" => $recordId]);
	}

	/**
	 * @return string
	 */
	protected function getQuery(): string {
		return <<< QUERY
			SELECT program_id, program_type_id, program_type, program, 
				programs.description, max_rating, availability, book_id, 
			    book, page 
			    
			FROM programs 
			INNER JOIN programs_types USING (program_type_id) 
			INNER JOIN books USING (book_id)
QUERY;
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function readAll(): array {
		$sql = $this->getQuery() . " ORDER BY program";
		return $this->db->getResults($sql);
	}

	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return !$isCollection ? $records[0]["program"] : "Programs";
	}

}