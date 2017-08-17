<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\AbstractDomain;

class QualitiesDomain extends AbstractDomain {
	/**
	 * @param bool $view
	 *
	 * @return array [string, string, string]
	 */
	protected function getRecordDetails($view = false): array {
		return ["quality_id", (!$view ? "qualities" : "qualities_view"), "quality"];
	}
	
	/**
	 * @param int $qualityId
	 *
	 * @return array
	 */
	protected function readOne(int $qualityId): array {
		$sql = "SELECT quality_id, quality, description, metagenetic,
			freakish, cost, minimum, maximum, book_id, book, abbr, page
			FROM qualities_view WHERE quality_id = :quality_id
			ORDER BY quality";
		
		return $this->db->getRow($sql, ["quality_id" => $qualityId]);
	}
	
	/**
	 * @return array
	 */
	protected function readAll(): array {
		return $this->db->getResults("SELECT quality_id, quality,
			description, metagenetic, freakish, cost, minimum, maximum,
			book_id, book, abbr, page FROM qualities_view
			ORDER BY quality");
	}
	
	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return !$isCollection ? $records[0]["quality"] : "Qualities";
	}
	
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function getNextRecordCriteria(array $record) {
		$criteria = parent::getNextRecordCriteria($record);
		
		// in addition to the default criteria, we also want to try and
		// get a quality of the same cost type (i.e. positive or negative).
		// so, we add that to the array and return it.
		
		if (isset($record["minimum"])) {
			$costType = $record["minimum"] > 0 ? ">" : "<";
			$criteria[] = "minimum $costType 0";
		}
		
		return $criteria;
	}
}
