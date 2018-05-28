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

function getMovement(object $critter) {
	if (isset($critter->movement)) {
		$temp = (string) $critter->movement;
		return $temp === "Special" ? $temp : "x2/x4/+2";
	}

	// if we're still here, then we want to decipher the walk,
	// run, and sprint information in our $critter to determine
	// our movement string.  if these three properties don't
	// exist, then we'll return the default above.

	switch ((string) $critter->name) {
		case "Spirit of Air":
			return "x2/x4/+10";

		case "Spirit of Fire":
			return "x2/x4/+5";

		default:
			$walk = explode("/", (string) ($critter->walk ?? ""));
			$run = explode("/", (string) ($critter->run ?? ""));
			$sprint = explode("/", (string) ($critter->sprint ?? ""));

			$format = "x%d/x%d/+%d";
			$ground = [$walk[0], $run[0], $sprint[0]];
			$water = [$walk[1], $run[1], $sprint[1]];
			$air = [$walk[2], $run[2], $sprint[2]];

			$movement = vsprintf($format, $ground);

			if ($water != [0, 0, 0] && $water != [1, 0, 1] && $water != [2, 4, 2]) {
				$movement .= " (" . vsprintf($format, $water) . " swimming)";
			}

			if ($air != [0, 0, 0] && $air != [2, 4, 2]) {
				$movement .= " (" . vsprintf($format, $air) . " flying)";
			}

			return $movement;
	}
}

try {
	$db = new Database();
	$xml = file_get_contents("data/critters.xml");
	$xml = new SimpleXMLElement($xml);

	foreach ($xml->categories->category as $category) {
		try {

			// critter type is a unique key, so we can just insert
			// and if the database blocks us, it'll throw an exception.
			// we catch it below and simple do nothing with it.

			$db->insert("critters_types", [
				"critter_type" => $category,
			]);
		} catch (DatabaseException $e) {
			continue;
		}
	}

	$books = $db->getMap("SELECT abbreviation, book_id FROM books");
	$types = $db->getMap("SELECT critter_type, critter_type_id FROM critters_types");
	$attributes = $db->getMap("SELECT attribute, attribute_id FROM attributes");
	$powers = $db->getMap("SELECT critter_power, critter_power_id FROM critter_powers");

	$attrMap = [
		"bodmin" => "body",
		"agimin" => "agility",
		"reamin" => "reaction",
		"strmin" => "strength",
		"chamin" => "charisma",
		"intmin" => "intuition",
		"logmin" => "logic",
		"wilmin" => "willpower",
		"inimin" => "initiative",
		"edgmin" => "edge",
		"magmin" => "magic",
		"resmin" => "resonance",
		"depmin" => "depth",
		"essmin" => "essence",
	];

	$movements = [];
	foreach ($xml->metatypes->metatype as $critter) {

		// the critters.xml file includes critters from SR4
		// as well as SR5.  we're only listing SR5 stats at
		// this time, so we'll skip any of them that cannot
		// be found in the $books map.

		$bookId = $books[(string) $critter->source] ?? false;
		if ($bookId === false) {
			continue;
		}

		$critterData = [
			"critter_type_id" => $types[(string) $critter->category],
			"movement" => getMovement($critter),
			"page" => (int) $critter->page,
			"book_id" => $bookId,
		];

		$guid["guid"] = strtolower((string) $critter->id);
		$critterName["critter"] = (string) $critter->name;
		$critterId = $db->upsert("critters", array_merge($critterName, $critterData, $guid), $critterData);

		// now, we have a lot of work to do to connect this new
		// critter in the database to its attributes, skills, powers,
		// and programs.  to make things as easy as possible, we'll
		// simply delete information in the database when we have
		// data to insert from the XML.  we'll start with attributes
		// since they all have that.

		$db->delete("critters_attributes", ["critter_id" => $critterId]);

		foreach ($attrMap as $property => $attribute) {
			if (isset($critter->{$property})) {
				$db->insert("critters_attributes", [
					"critter_id" => $critterId,
					"attribute_id" => $attributes[$attribute],
					"rating" => (string) $critter->{$property}
				]);
			}
		}

		// for our other information, the XML sometimes uses
		// self-closing tags (like <optionalpowers /> which
		// might result in a set property with no information.
		// so, we'll loop over the property's children building
		// the information we need before we delete.

		if (isset($critter->powers) ) {
			$powers = [];
			foreach ($critter->powers->power as $power) {
				$temp = [
					"critter_id" => $critterId,
					"critter_power_id" => $powers[(string) $power],
					"optional" => "N",
				];

				$powerAttributes = $power->attributes();
				if (isset($powerAttributes["select"])) {
					$temp["description"] = $powerAttributes["select"];
				}

				if (isset($powerAttributes["rating"])) {
					$temp["rating"] = $powerAttributes["rating"];
				}

				$powers[] = $temp;
			}

			if (sizeof($powers) > 0) {
				$db->delete("critters_critter_powers", ["critter_id" => $critterId]);
				foreach ($powers as $power) {
					$db->insert("critters_critter_powers", $power);
				}
			}

		}
	}

	echo "done";
} catch (DatabaseException $e) {
	die($e->getMessage());
}