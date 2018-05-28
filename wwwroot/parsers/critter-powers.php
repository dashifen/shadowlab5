<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Shadowlab\Framework\Database\Database;

function debug(...$x) {
	$dumps = [];
	foreach ($x as $y) {
		$dumps[] = print_r($y, true);
	}

	echo "<pre>" . join("</pre><pre>", $dumps) . "</pre>";
}

try {
	$db = new Database();
	$books = $db->getMap("SELECT abbreviation, book_id FROM books");
	$xml = file_get_contents("data/critterpowers.xml");
	$xml = new SimpleXMLElement($xml);

	foreach ($xml->categories->category as $category) {
		try {

			// critter power type is a unique key, so we can just insert
			// and if the database blocks us, it'll throw an exception.
			// we catch it below and simple do nothing with it.

			$db->insert("critters_powers_types", [
				"critter_power_type" => $category
			]);
		} catch (DatabaseException $e) {
			continue;
		}
	}

	$powerTypes = $db->getMap("SELECT critter_power_type, critter_power_type_id FROM critters_powers_types");

	// for our first pass through our critter powers, we want to enumerate
	// the types, actions, ranges, and durations.  we'll gather them all
	// together, and then see if the table has the right ENUM values to
	// accommodate our data.

	$types = [];
	$ranges = [];
	$actions = [];
	$durations = [];
	foreach ($xml->powers->power as $power) {
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

	$quit = false;
	foreach ($lists as $list => $data) {

		// for each of the lists, we get the list of values from the
		// database.  then, we see if there are any differences between
		// the database list and the one we collected above.  if so, we
		// print instructions and then quit after this loop.

		$enum_values = $db->getEnumValues("critters_powers", $list);
		$difference = array_diff($data, $enum_values);
		if (sizeof($difference) !== 0) {
			echo "Must add the following to critters_powers.$list:";
			debug($difference);
			$quit = true;
		}
	}

	if ($quit) {
		die();
	}

	// now that we know that our database table is prepared for this
	// list of powers, we'll insert them into the table as we do in some
	// of our other parsers.

	foreach ($xml->powers->power as $power) {
		$bookId = $books[(string) $power->source] ?? false;
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

		foreach (array_keys($lists) as $column) {
			if (!empty($value = (string) $power->{$column})) {
				$data[$column] = $value;
			}
		}

		// when inserting a power, we want to include the key, name, and
		// other data.  but, when we're updating based on the key, we want
		// to only update the data.  hence the oddly merged information
		// in a few lines.

		$guid["guid"] = strtolower((string) $power->id);
		$critter_power["critter_power"] = (string) $power->name;
		$db->upsert("critters_powers", array_merge($guid, $critter_power, $data), $data);
	}

	echo "done";
} catch (DatabaseException $e) {
	die($e->getMessage());
}