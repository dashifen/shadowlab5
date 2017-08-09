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
				$payload->setDatum("nextId", $this->getNextId());
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
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	public function update(array $data): PayloadInterface {
		
		// this is our entry point from the SpellAction object.  like that
		// object, we need to determine if we're collecting data from our
		// database so that it can be updated or if we're saving data that's
		// already been sent here by a visitor.  we know this based on
		// whether or not that posted data can be found within $data.
		
		$method = !isset($data["posted"]) ? "getDataToUpdate" : "savePostedData";
		return $this->{$method}($data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	protected function getDataToUpdate(array $data): PayloadInterface {
		
		// getting data to update is actually a special sort of read action.
		// luckily, we already know how to do that using the readOne() method
		// above.
		
		$spells = $this->db->getCol("SELECT spell_id FROM spells");
		$validation_data = array_merge($data, ["spells" => $spells]);
		if ($this->validator->validateRead($validation_data)) {
			
			// if we've validated the spell ID that was sent to us here,
			// then we're ready to read the information out of the database.
			
			$payload = $this->readOne($data["spell_id"]);
			if ($payload->getSuccess()) {
				
				// in order to build our form, our FormBuilder objects
				// need to have two additional payload data sent to them:
				// the database schema from which we've selected our data
				// and the index within our $payload where that data can
				// be found.  we call that later index "values" because it
				// represents the values found in (or going into) the
				// schema.
				
				$payload->setDatum("values", "spells");
				$payload->setDatum("schema", $this->getTableDetails());
				$payload = $this->transformer->transformUpdate($payload);
			}
			
			return $payload;
		}
		
		// if we have not already returned, then we'll report failure to the
		// calling scope.
		
		return $this->payloadFactory->newUpdatePayload(false, [
			"error" => $this->validator->getValidationErrors(),
		]);
	}
	
	/**
	 * @param string $table
	 *
	 * @return array
	 */
	protected function getTableDetails(string $table = "spells"): array {
		$schema = parent::getTableDetails($table);
		
		// spells have a one-to-many relationship with tags.  this means
		// that the tags are actually located in their own table and their
		// relationship with spells gets a table, too.  our parent's method
		// can't get those data from the spells table, so we have to do
		// that here by hand.  to do so, we get the schema for the table
		// which stores our relationships (i.e. spells_spell_tags) and then
		// get the spell_tag_id information out of it.  we set a MULTIPLE
		// flag on it so that our form builder knows it should be a
		// SelectMany field and not a SelectOne.
		
		$tags = parent::getTableDetails("spells_spell_tags");
		$spell_tag_id = array_merge($tags["spell_tag_id"], [
			"VALUES_KEY" => "spell_tags_ids",
			"MULTIPLE"   => true,
		]);
		
		// some of our spells, e.g. Manablade in Hard Targets, don't use
		// tags, so we'll make sure that these data are option when editing
		// information.
		
		$spell_tag_id["IS_NULLABLE"] = "YES";
		
		// finally, we want to insert that new array into the $schema one
		// after the description so it appears in the right place of our form.
		
		$temp = [];
		foreach ($schema as $field => $value) {
			$temp[$field] = $value;
			
			if ($field === "description") {
				$temp["spell_tag_id"] = $spell_tag_id;
			}
		}
		
		return $temp;
	}
	
	protected function savePostedData(array $data): PayloadInterface {
		
		// to validate our posted data, we need to know more about the
		// table into which we're going to be inserting it.  so, we'll
		// get its schema and then pass everything over to our validator.
		
		$validationData = [
			"posted" => $data["posted"],
			"schema" => $this->getTableDetails(),
		];
		
		if ($this->validator->validateUpdate($validationData)) {
			
			// if we've validated our data, we're ready to put it into the
			// database.  right now, our posted data is a mix of the spell_id
			// and the rest of the data that we want to update.  note that we
			// handle the tags separately from our tags because of the one-
			// to-many relationship between spells and their tags.
			
			$spellId = $this->saveSpell($data["posted"]);
			$this->saveSpellTags($spellId, $data["posted"]["spell_tag_id"] ?? []);
			
			// after a successful update, we want to give our visitor the
			// ability to quickly update the next un-described book in the
			// database.  so, we'll get the "next" ID and send it back in
			// our payload.
			
			return $this->payloadFactory->newUpdatePayload(true, [
				"title"   => $data["posted"]["spell"],
				"nextId"  => $this->getNextId($data["posted"]),
				"thisId" => $spellId,
			]);
		}
		
		// if we didn't return within the if-block above, then we want
		// to report a failure to update.  we want to be sure to send back
		// the information necessary to re-display our form along with the
		// posted data, too.  then, a quick pass through our transformer,
		// just in case it's necessary we do so, and we're done here.
		
		$payload = $this->payloadFactory->newUpdatePayload(false, [
			"error"  => $this->validator->getValidationErrors(),
			"schema" => $validationData["schema"],
			"posted" => $validationData["posted"],
			"values" => "posted",
		]);
		
		return $this->transformer->transformUpdate($payload);
	}
	
	/**
	 * @param array $spell
	 *
	 * @return int
	 */
	protected function saveSpell(array $spell): int {
		
		// to save our spell, we want to separate the ID from the rest of
		// the data that lives in the spells table.  then, we separate the
		// rest of that data from the ID (and the tags, since they don't
		// live there), and cram it all into the database.
		
		$spellId = $spell["spell_id"] ?? 0;
		$skipThese = ["spell_id", "spell_tag_id"];
		
		$spellData = array_filter($spell, function($index) use ($skipThese) {
			
			// our spell's data constitutes the information in $spell that
			// is not indexed by the values within $skipThese.  so, when we
			// find one of those, we return false; otherwise, true.
			
			return !in_array($index, $skipThese);
		}, ARRAY_FILTER_USE_KEY);
		
		if ($spellId != 0) {
			$this->db->update("spells", $spellData, ["spell_id" => $spellId]);
		} else {
			$spellId = $this->db->insert("spells", $spellData);
		}
		
		return $spellId;
	}
	
	/**
	 * @param int   $spellId
	 * @param array $tags
	 *
	 * @return void
	 */
	protected function saveSpellTags(int $spellId, array $tags): void {
		
		// in a professional database system, keeping a log of changes is
		// important.  in an enthusiasts hobby, we can just remove the
		// current tags for this spell and then insert the ones in $tags.
		
		$this->db->delete("spells_spell_tags", ["spell_id" => $spellId]);
		
		if (sizeof($tags) > 0) {
			$insertions = [];
			foreach ($tags as $tag) {
				$insertions[] = [
					"spell_id"     => $spellId,
					"spell_tag_id" => $tag,
				];
			}
			
			$this->db->insert("spells_spell_tags", $insertions);
		}
	}
	
	/**
	 * @param array $spell
	 *
	 * @return int
	 */
	protected function getNextId(array $spell = []): int {
		
		// our next spell is the the next alphabetical spell without a
		// description in the same book and category.  if we don't get a
		// spell, then we just get first alphabetical one and the visitor
		// can start from there.
		
		$statement = sizeof($spell) > 0
			? "SELECT spell_id FROM spells WHERE description IS NULL
					AND spell_category_id = :spell_category_id AND book_id = :book_id
					ORDER BY spell"
			
			: "SELECT spell_id FROM spells WHERE description IS NULL ORDER BY spell";
		
		return $this->db->getVar($statement, $spell);
	}
}
