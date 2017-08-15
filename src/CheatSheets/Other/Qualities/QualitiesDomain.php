<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\AbstractDomain;

class QualitiesDomain extends AbstractDomain {
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
	 * @param array $quality
	 *
	 * @return int
	 */
	protected function getNextId(array $quality = []): int {
		
		// the next quality to describe is the one in the same category
		// and book after the one we've received.  if we didn't get one,
		// then we'll get it out of the database.
		
		if (sizeof($quality) === 0) {
			$quality = $this->getNextBlankQuality();
		}
		
		// still here?  then we have a quality to work with.  first, we see
		// if we can get the next one in this category and this book.  for our
		// category, we can see if our $minimum is greater than or less than
		// zero.
		
		$category = ($quality["minimum"] ?? 1) > 0 ? ">" : "<";
		
		$where = [
			"description IS NULL",
			"book_id = :book_id",
			"minimum $category 0",
		];
		
		while (sizeof($where) > 0) {
			$whereStr = join(" AND ", $where);
			$sql = "SELECT quality_id FROM qualities WHERE $whereStr ORDER BY quality";
			$quality_id = $this->db->getVar($sql, $quality);
			
			if (is_numeric($quality_id)) {
				return $quality_id;
			}
			
			// if we haven't returned, then we want to iteratively relax the
			// criteria we use to identify the next quality.  we'll pop off
			// the last criteria and keep the remaining ones.  we'll do this
			// until we have non left.
			
			array_pop($where);
		}
		
		// if we're outside the loop, we'll just return the next blank one.
		// this should be the same as having popped off the book and category
		// criteria, but just in case something gets weird, we'll do things
		// this way.
		
		return $this->getNextBlankQuality()["quality_id"];
	}
	
	/**
	 * @return array
	 */
	protected function getNextBlankQuality(): array {
		return $this->db->getRow("SELECT quality_id, book_id
			FROM qualities WHERE description IS NULL
			ORDER BY quality");
	}
	
	/**
	 * @return array
	 */
	protected function getRecords(): array {
		return $this->db->getCol("SELECT quality_id FROM qualities");
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
	
	
}
