<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractDomain;

class SpellsDomain extends AbstractDomain {
	/**
	 * @return array
	 */
	protected function getRecords(): array {
		return $this->db->getCol("SELECT spell_id FROM spells");
	}
	
	/**
	 * @param int $spell_id
	 *
	 * @return array
	 */
	protected function readOne(int $spell_id): array {
		
		// we've prepared a spells_view in the database that makes our select
		// query here more simple.  it handles the joins and other difficult
		// stuff so that here we can simply select from the results thereof.
		
		$sql = "SELECT spell_id, spell, spell_category, spell_tags,
			description, type, `range`, damage, duration, drain_value,
			abbr, page, spell_tags_ids, spell_category_id, book_id, book
			FROM spells_view WHERE spell_id = :spell_id";
		
		return $this->db->getRow($sql, ["spell_id" => $spell_id]);
	}
	
	/**
	 * @return array
	 */
	protected function readAll(): array {
		
		// we've prepared a spells_view in the database that makes our select
		// query here more simple.  it handles the joins and other difficult
		// stuff so that here we can simply select from the results thereof.
		
		$sql = "SELECT spell_id, spell, spell_category, spell_tags,
			description, type, `range`, damage, duration, drain_value,
			abbr, page, spell_tags_ids, spell_category_id, book_id, book
			FROM spells_view ORDER BY spell";
		
		return $this->db->getResults($sql);
	}
	
	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return !$isCollection ? $records[0]["spell"] : "Spells";
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
	
	protected function saveRecord(string $table, PayloadInterface $payload, array $key) {
		
		// saving a spell is a little more complicated because our information
		// about tags is separated from the rest of the spell data.  so, we've
		// separated our information into two different methods that follow
		// this one.
		
		$spell = $payload->getDatum("record");
		$this->saveSpell($spell, $key);
		$this->saveSpellTags($spell, $key);
	}
	
	/**
	 * @param array $spell
	 * @param array $key
	 *
	 * @return int
	 */
	protected function saveSpell(array $spell, array $key): int {
		
		// our $key contains our spell_id.  if it's not zero, we
		// update; if it's zero we insert.
		
		$spellId = $key["spell_id"];
		unset($spell["spell_tag_id"]);
		
		if ($spellId != 0) {
			$this->db->update("spells", $spell, $key);
		} else {
			$spellId = $this->db->insert("spells", $spell);
		}
		
		return $spellId;
	}
	
	/**
	 * @param array $spell
	 * @param array $key
	 *
	 * @return void
	 */
	protected function saveSpellTags(array $spell, array $key): void {
		$tags = $spell["spell_tag_id"];
		
		// in a professional database system, keeping a log of changes is
		// important.  in an enthusiasts hobby, we can just remove the
		// current tags for this spell and then insert the ones in $tags.
		
		$this->db->delete("spells_spell_tags", $key);
		
		if (sizeof($tags) > 0) {
			$insertions = [];
			foreach ($tags as $tag) {
				$insertions[] = [
					"spell_id"     => $key["spell_id"],
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
