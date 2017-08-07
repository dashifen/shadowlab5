<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\Domain;

class SpellsDomain extends Domain {
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	public function read(array $data = []): PayloadInterface {
		
		// the only data we expect to receive from our action is a sanitized
		// version of a spells name.  we can get those from our database and
		// use our validator to be sure that we're good to go.
		
		$spells = $this->db->getCol("SELECT spell_id FROM spells");
		$validation_data = array_merge($data, ["spells" => $spells]);
		if ($this->validator->validateRead($validation_data)) {
			
			// now, we have one of two payloads to create:  a single spell or
			// all of our spells.  whether or not we have a sanitized version
			// of a spell determines which we do.
			
			$payload = !empty($data["spell_id"])
				? $this->readOne($data["spell_id"])
				: $this->readAll();
			
			if ($payload->getSuccess()) {
				$payload = $this->transformer->transformRead($payload);
			}
		} else {
			// if our validation data were invalid, then we'll grab the
			// errors that our validator ran into and pass back a failed
			// payload.
			
			$payload = $this->payloadFactory->newReadPayload(false, [
				"error" => $this->validator->getValidationErrors(),
			]);
		}
		
		return $payload;
	}
	
	/**
	 * @param int $spell_id
	 *
	 * @return PayloadInterface
	 */
	protected function readOne(int $spell_id): PayloadInterface {
		
		// we've prepared a spells_view in the database that makes our select
		// query here more simple.  it handles the joins and other difficult
		// stuff so that here we can simply select from the results thereof.
		
		$sql = "SELECT spell_id, spell, spell_category, spell_tags,
			description, type, `range`, damage, duration, drain_value,
			abbr, page, spell_tags_ids, spell_category_id, book_id, book
			FROM spells_view WHERE spell_id = :spell_id";
		
		$spell = $this->db->getRow($sql, ["spell_id" => $spell_id]);
		
		// success is when we have read a spell.  then, we'll load it into our
		// payload at the "spells" index so that our payload has the same
		// structure as the one from readAll below.
		
		return $this->payloadFactory->newReadPayload(sizeof($spell) > 0, [
			"title"  => $spell["spell"],
			"spells" => $spell,
			"count"  => 1,
		]);
	}
	
	/**
	 * @return PayloadInterface
	 */
	protected function readAll(): PayloadInterface {
		
		// we've prepared a spells_view in the database that makes our select
		// query here more simple.  it handles the joins and other difficult
		// stuff so that here we can simply select from the results thereof.
		
		$sql = "SELECT spell_id, spell, spell_category, spell_tags,
			description, type, `range`, damage, duration, drain_value,
			abbr, page, spell_tags_ids, spell_category_id, book_id, book
			FROM spells_view ORDER BY spell";
		
		$spells = $this->db->getResults($sql);
		
		// like above, success is when we have read spells from the database.
		// we'll cram them into a payload with a more general title than we
		// use above, and then we send it all back to the calling scope.
		
		$count = sizeof($spells);
		return $this->payloadFactory->newReadPayload($count > 0, [
			"title"  => "Spells",
			"spells" => $spells,
			"count"  => $count,
		]);
	}
}
