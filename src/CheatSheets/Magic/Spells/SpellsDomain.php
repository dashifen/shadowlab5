<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Domain\AbstractDomain;

class SpellsDomain extends AbstractDomain {
    /**
     * @param bool $view
     *
     * @return array [string, string, string]
     */
    protected function getRecordDetails($view = false): array {
        return ["spell_id", (!$view ? "spells" : "spells_view"), "spell"];
    }

    /**
     * @param int $spell_id
     *
     * @return array
     */
    protected function readOne(int $spell_id): array {
        $sql = $this->getQuery() . " WHERE spell_id = :spell_id";
        return $this->db->getRow($sql, ["spell_id" => $spell_id]);
    }

    /**
     * @return string
     */
    protected function getQuery(): string {

        // we've prepared a spells_view in the database that makes our select
        // query here more simple.  it handles the joins and other difficult
        // stuff so that here we can simply select from the results thereof.

        return "SELECT spell_id, spell, spell_category, spell_tags,
			description, type, `range`, damage, duration, drain_value,
			abbreviation, page, spell_tags_ids, spell_category_id, book_id,
			book FROM spells_view";
    }

    /**
     * @return array
     */
    protected function readAll(): array {
        $sql = $this->getQuery() . " ORDER BY spell";
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
     * @param bool   $withFKOptions
     *
     * @return array
     */
    protected function getTableDetails(string $table, bool $withFKOptions = true): array {
        $schema = parent::getTableDetails($table);

        // spells have a one-to-many relationship with tags.  this means
        // that the tags are actually located in their own table and their
        // relationship with spells gets a table, too.  our parent's method
        // can't get those data from the spells table, so we have to do
        // that here by hand.

        $tags = parent::getTableDetails("spells_spell_tags");
        $spell_tag_id = array_merge($tags["spell_tag_id"], [

            // these additions inform our FormBuilder about these data.
            // first, we tell it where to find new values after data is
            // posted to the server.  then, it needs to know both that
            // these data are optional (e.g. Manablade) and that they
            // should be presented as a SelectMany field on-screen.

            "VALUES_KEY"  => "spell_tags_ids",
            "IS_NULLABLE" => "YES",
            "MULTIPLE"    => true,
        ]);

        $schema = $this->addToSchemaAfter($schema, "description", $spell_tag_id, "spell_tag_id");
        return $schema;
    }

    /**
     * @param string $table
     * @param array  $record
     * @param array  $key
     *
     * @return int
     */
    protected function saveRecord(string $table, array $record, array $key): int {

        // saving a spell is a little more complicated because our information
        // about tags is separated from the rest of the spell data.  so, we've
        // separated our information into two different methods that follow
        // this one.

        $spellId = $this->saveSpell($record, $key);

        // in case we were inserting a spell, we'll want to put that spell ID
        // into our $key.  if it was already there, then there's no harm and
        // no foul.  but, if it wasn't there, without it the following method
        // would fail.

        $key["spell_id"] = $spellId;
        $this->saveSpellTags($record, $key);
        return $spellId;
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

    protected function getNextRecordCriteria(array $record) {
        $criteria = parent::getNextRecordCriteria($record);

        // in addition to the default criteria, we want to try and select a
        // spell from the same category as our current record:

        if (isset($record["spell_category_id"])) {
            $criteria[] = sprintf("spell_category_id = %d", $record["spell_category_id"]);
        }

        return $criteria;
    }
}
