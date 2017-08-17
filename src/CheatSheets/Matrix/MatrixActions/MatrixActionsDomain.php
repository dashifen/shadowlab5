<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Shadowlab\Framework\Domain\AbstractDomain;

class MatrixActionsDomain extends AbstractDomain {
	/**
	 * @param bool $view
	 *
	 * @return array [string, string, string]
	 */
	protected function getRecordDetails($view = false): array {
		return ["matrix_action_id", "matrix_actions", "matrix_action"];
	}
	
	/**
	 * @param int $recordId
	 *
	 * @return array
	 */
	protected function readOne(int $recordId): array {
		$sql = $this->getQuery() . " WHERE matrix_action_id = :matrix_action_id";
		return $this->db->getRow($sql, ["matrix_action_id" => $recordId]);
	}
	
	protected function getQuery(): string {
		return "SELECT matrix_action_id, matrix_action, ma.description,
			test, marks, action, book_id, book, abbr, page FROM matrix_actions ma
			INNER JOIN books USING (book_id) ";
	}
	
	/**
	 * @return array
	 */
	protected function readAll(): array {
		return $this->db->getResults($this->getQuery() . " ORDER BY matrix_action");
	}
	
	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return $isCollection ? "Matrix Actions" : $records[0]["matrix_action"];
	}
}
