<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class SpellsParser extends AbstractParser {
	/**
	 * @var array
	 */
	protected $tags = [];

	/**
	 * @var array
	 */
	protected $categories = [];

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		$this->updateSpellTags();
		$this->updateIdTable("categories", "spell_category", "spells_categories");
		$this->categories = $this->db->getMap("SELECT spell_category, spell_category_id FROM spells_categories");
		$this->tags = $this->db->getMap("SELECT spell_tag, spell_tag_id FROM spells_tags");

		if ($this->canContinue()) {
			foreach ($this->xml->spells->spell as $xmlSpell) {
				$guid = strtoupper((string) $xmlSpell->id);

				// a limited number of spells lack a GUID.  if we
				// don't have one, then we'll just move on and we'll
				// worry about those later.

				if (!empty($guid)) {
					$spell = $this->getSpellData($xmlSpell);

					$insertData = array_merge($spell, [
						"spell" => (string) $xmlSpell->name,
						"guid"  => $guid,
					]);

					$this->db->upsert("spells", $insertData, $spell);
					$this->insertSpellTags($xmlSpell, $guid);
				}
			}
		}
	}

	/**
	 * @throws DatabaseException
	 */
	protected function updateSpellTags(): void {

		// we can't use the updateIdTable() method for the spell tags
		// because they're listed as a comma separated string within each
		// spell.  instead, we gather our tags and then see if there's
		// anything to do with them.

		if (sizeof(($tags = $this->getTags())) > 0) {

			// if we have found tags, then, we'll see if there are any
			// to insert.  we get the ones that are already in the database.
			// with the array_diff() function, we can see which of the ones
			// we found are new.  then, we insert them.

			$dbTags = $this->db->getCol("SELECT spell_tag FROM spells_tags");
			$newTags = array_diff($tags, $dbTags);

			if (sizeof($newTags) > 0) {
				$insertions = [];

				foreach ($newTags as $tag) {
					$insertions[] = ["spell_tag" => $tag];
				}

				$this->db->insert("spells_tags", $insertions);
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getTags(): array {
		$tags = [];

		// we'll loop over our spells, extracting the descriptor, as
		// chummer calls it.  this is the comma separated string that
		// makes up our tags.

		foreach ($this->xml->spells->spell as $spell) {
			$tagsAsString = (string) $spell->descriptor;
			$tags = array_merge($tags, explode(", ", $tagsAsString));
		}

		// since it's guaranteed that we do not have a unique list
		// of tags, we'll send things through the following filters.
		// when we're done, we'll return them to the callings scope.

		return $this->filter($tags);
	}

	/**
	 * @param array $collection
	 *
	 * @return array
	 */
	protected function filter(array $collection): array {

		// given a collection, this method ensures that it's
		// unique.  and, it makes sure that the items within
		// it aren't empty or made of only whitespace.

		$unique = array_unique($collection);
		return array_filter($unique, function($str) {
			return !empty(trim($str));
		});
	}

	/**
	 * @return bool
	 * @throws MysqlException
	 */
	protected function canContinue(): bool {

		// we'll know if we can continue based on whether or not the
		// ENUM columns in the database have the necessary value options.
		// to know this, we first need to get those options out of the
		// XML as follows.

		$canContinue = true;
		$lists = $this->collectEnumValueOptions();
		foreach ($lists as $list => $data) {

			// now, we want to be sure that the spells table can handle it.  each
			// of the lists here matches the name of a column in that table, and
			// we'll want to make sure that the ENUM values therein match our data.

			$enum_values = $this->db->getEnumValues("spells", $list);
			$difference = array_diff($data, $enum_values);
			if (sizeof($difference) !== 0) {
				echo "Must add the following to spells.$list:";
				$this->debug($difference);
				$canContinue = false;
			}
		}

		return $canContinue;
	}

	/**
	 * @return array
	 */
	protected function collectEnumValueOptions(): array {
		$durations = [];
		$damages = [];
		$ranges = [];
		$types = [];

		// the above collections are the ones we want to fill.  so,
		// we'll loop over our spells and extract the values that we
		// encounter in our list.

		foreach ($this->xml->spells->spell as $spell) {
			$durations[] = (string) $spell->duration;
			$damages[] = (string) $spell->damage;
			$ranges[] = (string) $spell->range;
			$types[] = (string) $spell->type;
		}

		// since we definitely have non-unique arrays, we'll send them
		// through our

		return [
			"duration" => $this->filter($durations),
			"damage"   => $this->filter($damages),
			"range"    => $this->filter($ranges),
			"type"     => $this->filter($types),
		];
	}

	/**
	 * @param SimpleXMLElement $spell
	 *
	 * @return array
	 */
	protected function getSpellData(SimpleXMLElement $spell): array {
		$spell = [
			"spell_category_id" => $this->categories[(string) $spell->category],
			"type"              => (string) $spell->type,
			"range"             => (string) $spell->range,
			"damage"            => (string) $spell->damage,
			"duration"          => (string) $spell->duration,
			"drain_value"       => (string) $spell->dv,
			"book_id"           => $this->bookMap[(string) $spell->source],
			"page"              => (string) $spell->page,
		];

		// the XML seems to have forgotten the "F" sometimes for -4
		// spells. we'll make sure it's added back in here.

		if ($spell["drain_value"] === "-4") {
			$spell["drain_value"] = "F-4";
		}

		return $spell;
	}

	/**
	 * @param SimpleXMLElement $spell
	 * @param string           $guid
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function insertSpellTags(SimpleXMLElement $spell, string $guid): void {
		$spellId = $this->db->getVar(
			"SELECT spell_id FROM spells WHERE guid=:guid",
			["guid" => $guid]
		);

		// first, we want to get a list of the tags that this spell
		// should have from the XML.  we'll extract them using a similar
		// line of code as we did above.  then we convert them to IDs and
		// compare them to the tags already in the database (if any) for
		// this spell.

		$tags = $this->filter(explode(", ", (string) $spell->descriptor));
		foreach ($tags as $i => $tag) {
			$tags[$i] = $this->tags[$tag];
		}

		// now, we want to get the list of tags for this spell out of
		// the database.  array_diff() can tell us the tags that are
		// on this spell but not in the database, and any in the
		// database that are no longer on the spell.  with those, we
		// can insert and delete.

		$dbTags = $this->db->getCol("SELECT spell_tag_id FROM spells_spell_tags WHERE spell_id=:spell_id", ["spell_id" => $spellId]);
		$additions = array_diff($tags, $dbTags);    // tags in the XML that aren't in the database
		$removals = array_diff($dbTags, $tags);     // tags in the database that aren't in the XML

		if (sizeof($additions) > 0) {

			// if we found additions, we'll loop over them and add them
			// to the database.  we could collect them all to do a single
			// insertion, but there's not so many tags that we're too
			// worried that it'll be a big deal.

			foreach ($additions as $i => $tagId) {
				$this->db->insert("spells_spell_tags", [
					"spell_id"     => $spellId,
					"spell_tag_id" => $tagId,
				]);
			}
		}

		if (sizeof($removals) > 0) {

			// similarly, if we have anything to remove, we can use
			// the list to set up a DELETE query.

			$sql = "DELETE FROM spells_spell_tags WHERE spell_id=:spell_id AND spell_tag_id IN (:tags)";
			$this->db->runQuery($sql, ["spell_id" => $spellId, "tags" => $removals]);
		}
	}
}

try {
	$parser = new SpellsParser("data/spells.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
