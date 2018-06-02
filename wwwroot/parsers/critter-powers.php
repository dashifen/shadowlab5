<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class CritterPowersParser extends AbstractParser {
	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		$this->updateIdTable("categories", "critter_power_type", "critter_powers_types");

		if ($this->canContinue()) {
			$powerTypes = $this->db->getMap("SELECT critter_power_type, critter_power_type_id FROM critter_powers_types");
			$optionalColumns = ["duration", "action", "range", "type"];

			// now that we know that our database table is prepared
			// for this list of powers, we'll insert them into the
			// table as we do in some of our other parsers.

			foreach ($this->xml->powers->power as $power) {
				$bookId = $books[(string) $power->source] ?? false;

				// some critter powers are from 4th edition in our XML.
				// since we're only listing 5th edition information in the
				// system at this time, if our $bookId is false, then
				// we can continue onto the next power.

				if ($bookId === false) {
					continue;
				}

				$data = [
					"critter_power_type_id" => $powerTypes[(string) $power->category],
					"rating"                => isset($power->rating) ? "Y" : "N",
					"toxic"                 => isset($power->toxic) ? "Y" : "N",
					"page"                  => (int) $power->page,
					"book_id"               => $bookId,
				];

				foreach ($optionalColumns as $column) {
					if (!empty($value = (string) $power->{$column})) {
						$data[$column] = $value;
					}
				}

				// when inserting a power, we want to include the key, name, and
				// other data.  but, when we're updating based on the key, we want
				// to only update the data.  hence the oddly merged information
				// in a few lines.

				$insertData = array_merge($data, [
					"critter_power" => (string) $power->name,
					"guid"          => strtolower((string) $power->id),
				]);

				$this->db->upsert("critter_powers", $insertData, $data);
			}
		}
	}

	/**
	 * @return bool
	 * @throws MysqlException
	 */
	protected function canContinue(): bool {
		$types = [];
		$ranges = [];
		$actions = [];
		$durations = [];

		// if the database table's ENUM columns don't have the right
		// value options, then we can't continue.  to determine if we're
		// good to go, we'll loop over our powers gathering the options
		// that are in the XML.

		foreach ($this->xml->powers->power as $power) {
			$durations[] = (string) $power->duration;
			$actions[] = (string) $power->action;
			$ranges[] = (string) $power->range;
			$types[] = (string) $power->type;
		}

		$lists = [
			"duration" => array_filter(array_unique($durations)),
			"action"   => array_filter(array_unique($actions)),
			"range"    => array_filter(array_unique($ranges)),
			"type"     => array_filter(array_unique($types)),
		];

		// now that we have unique, filtered lists of our needed
		// ENUM value options, we'll see if our database table
		// matches them.

		$canContinue = true;
		foreach ($lists as $list => $data) {

			// for each of the lists, we get the list of values from the
			// database.  then, we see if there are any differences between
			// the database list and the one we collected above.  if so, we
			// print instructions and then quit after this loop.

			$enum_values = $this->db->getEnumValues("critter_powers", $list);
			$difference = array_diff($data, $enum_values);
			if (sizeof($difference) !== 0) {
				echo "Must add the following to critter_powers.$list:";
				$this->debug($difference);
				$canContinue = false;
			}
		}

		return $canContinue;
	}
}


try {
	$parser = new CritterPowersParser("data/critterpowers.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
