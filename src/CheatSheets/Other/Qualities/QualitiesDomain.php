<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractDomain;

class QualitiesDomain extends AbstractDomain {
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	public function read(array $data = []): PayloadInterface {
		
		// when we're reading information, we need to be sure that any
		// quality ID that we get from our Action exists in the database.
		// so, we'll get the list of those IDs and pass them and our $data
		// over to the validator.
		
		$qualities = $this->db->getCol("SELECT quality_id FROM qualities");
		$validationData = array_merge($data, ["qualities" => $qualities]);
		if ($this->validator->validateRead($validationData)) {
			
			// if things are valid, then we want to either read the entire
			// collection of our qualities or the single one that's specified.
			// as long as that works, we'll add on the next quality to be
			// described and call it a day.
			
			$payload = !empty($data["quality_id"])
				? $this->readOne($data["quality_id"])
				: $this->readAll();
			
			if ($payload->getSuccess()) {
				$payload->setDatum("nextId", $this->getNextId());
				$payload = $this->transformer->transformRead($payload);
			}
		}
		
		// if we didn't create a payload inside the if-block above, then
		// we do so here.  this avoids needed extra else's because of the
		// two ifs in the above block.  with the beatific null coalescing
		// operator, we can do this all as a single statement.
		
		return $payload ?? $this->payloadFactory->newReadPayload(false, [
				"error" => $this->validator->getValidationErrors(),
			]);
	}
	
	/**
	 * @param int $qualityId
	 *
	 * @return PayloadInterface
	 */
	protected function readOne(int $qualityId): PayloadInterface {
		$sql = "SELECT quality_id, quality, description, metagenetic,
			freakish, cost, minimum, maximum, book_id, book, abbr, page
			FROM qualities_view WHERE quality_id = :quality_id
			ORDER BY quality";
		
		$quality = $this->db->getRow($sql, ["quality_id" => $qualityId]);
		
		// if we were successful in selecting a quality, then $quality's size
		// will be greater than zero.  that's how we can determine the type of
		// read payload we return.
		
		return $this->payloadFactory->newReadPayload(sizeof($quality) > 0, [
			"title"     => $quality["quality"],
			"qualities" => $quality,
			"count"     => 1,
		]);
	}
	
	/**
	 * @return PayloadInterface
	 */
	protected function readAll(): PayloadInterface {
		$qualities = $this->db->getResults("SELECT quality_id, quality,
			description, metagenetic, freakish, cost, minimum, maximum,
			book_id, book, abbr, page FROM qualities_view
			ORDER BY quality");
		
		return $this->payloadFactory->newReadPayload(sizeof($qualities) > 0, [
			"title"     => "Qualities",
			"qualities" => $qualities,
			"count"     => sizeof($qualities),
		]);
		
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
	
	
}
