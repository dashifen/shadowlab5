<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Shadowlab\Framework\Domain\AbstractDomain;
use Dashifen\Database\DatabaseException;

class AdeptPowersDomain extends AbstractDomain {
    /**
     * @param bool $view
     *
     * @return array [string, string, string]
     */
    protected function getRecordDetails($view = false): array {
        return ["adept_power_id", (!$view ? "adept_powers" : "adept_powers_view"),
            "adept_power"];
    }

	/**
	 * @param int $recordId
	 *
	 * @return array
	 * @throws DatabaseException
	 */
    protected function readOne(int $recordId): array {
        $sql = $this->getQuery() . " WHERE adept_power_id = :adept_power_id";
        return $this->db->getRow($sql, ["adept_power_id" => $recordId]);
    }

    /**
     * @return string
     */
    protected function getQuery(): string {
        return "SELECT adept_power_id, adept_power_ways_ids, adept_power,
			description, adept_power_ways, action, cost, maximum_levels,
			cost_per_level, book_id, book, abbreviation, page, levels
			FROM adept_powers_view ORDER BY adept_power";
    }

	/**
	 * @return array
	 * @throws DatabaseException
	 */
    protected function readAll(): array {
        return $this->db->getResults($this->getQuery());
    }

    /**
     * @param array $records
     * @param bool  $isCollection
     *
     * @return string
     */
    protected function getRecordsTitle(array $records, bool $isCollection): string {
        return $isCollection ? "Adept Powers" : $records[0]["adept_power"];
    }

	/**
	 * @param string $table
	 * @param bool   $withFKOption
	 *
	 * @return array
	 * @throws DatabaseException
	 */
    protected function getTableDetails(string $table, bool $withFKOption = true): array {
        $schema = parent::getTableDetails($table);

        // our adept powers schema also needs information about the ways
        // in which powers can appear.  like with spells and their tags, we
        // need to add that information to $schema.

        $waysSchema = parent::getTableDetails("adept_powers_ways", false);
        $adept_power_way_ids = array_merge($waysSchema["quality_id"], [

            // the first addition is the name of the value in our posted
            // data where we will be able to find new information about
            // these data.  also, since not all powers are in a way, this
            // field has to be nullable.  finally, we indicate to the
            // FormBuilder that this field should be displayed as a SelectMany
            // element.

            "VALUES_KEY"  => "adept_power_way_ids",
            "IS_NULLABLE" => "YES",
            "MULTIPLE"    => true,

            // finally, we 100% do not want all of our qualities to be
            // listed as the OPTIONS for this field.  we passed a false
            // to our parent's method above to save a little time so that
            // we can set our OPTIONS here.

            "OPTIONS" => $this->db->getMap("SELECT quality_id, quality
				FROM adept_ways_view ORDER BY quality"),
        ]);

        // and, now we want to add this information about our ways to the
        // $schema we selected about the adept_powers table.  we add it
        // after the description so it appears in the right part of the
        // form.

        $schema = $this->addToSchemaAfter($schema, "description",
            $adept_power_way_ids, "adept_power_way_ids");

        // finally, there's one last thing to do:  the XML we get lists
        // actions in all-lower-case.  we want to capitalize them as follows.

        foreach ($schema["action"]["OPTIONS"] as &$option) {
            $option = ucfirst($option);
        }

        return $schema;
    }

	/**
	 * @param string $table
	 * @param array  $record
	 * @param array  $key
	 *
	 * @return int
	 * @throws DatabaseException
	 */
    protected function saveRecord(string $table, array $record, array $key): int {

        // we split the information about our power into two tables:
        // adept_powers and adept_powers_ways.  this method handles that
        // split

        $powerId = $this->savePower($record, $key);

        // in case our power was inserted, we need to add our new ID to our
        // $key.  if it was already in there, then replacing it won't matter,
        // but without it, the following method would fail.

        $key = ["adept_power_id" => $powerId];
        $this->savePowerWays($record, $key);
        return $powerId;
    }

	/**
	 * @param array $power
	 * @param array $key
	 *
	 * @return int
	 * @throws DatabaseException
	 */

    protected function savePower(array $power, array $key): int {

        // here we want to store all of the information except that which
        // is about our ways.  the way information is contained at the
        // adept_power_way_ids index in $power.  we remove that and then
        // save the rest in the database.

        $power_id = $key["adept_power_id"];
        unset($power["adept_power_way_ids"]);

        if ($power_id != 0) {
            $this->db->update("adept_powers", $power, $key);
        } else {
            $power_id = $this->db->insert("adept_powers", $power);
        }

        return $power_id;
    }

	/**
	 * @param array $power
	 * @param array $key
	 *
	 * @return void
	 * @throws DatabaseException
	 */
    protected function savePowerWays(array $power, array $key): void {

        // here we simply want to save the information about the ways
        // that reduce the cost of this power.  first, we remove any
        // prior information about this power's ways.

        $this->db->delete("adept_powers_ways", $key);
        if (sizeof(($ways = ($power["adept_power_way_ids"] ?? []))) > 0) {

            // now that we've confirmed we have new $ways to insert,
            // we'll loop over them.  in so doing, we can prepare a
            // list of insertions that we then cram back into the
            // adept_powers_ways table.

            $insertions = [];
            foreach ($ways as $wayId) {
                $insertions[] = [
                    "adept_power_id" => $key["adept_power_id"],
                    "quality_id"     => $wayId,
                ];
            }

            $this->db->insert("adept_powers_ways", $insertions);
        }
    }
}
