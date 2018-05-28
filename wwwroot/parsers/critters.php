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
	$attrs = [
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

	$props = [];
	foreach ($xml->metatypes->metatype as $critter) {
		$bookId = $books[(string) $critter->source] ?? false;
		if ($bookId === false) {
			continue;
		}


	}


} catch (DatabaseException $e) {
	die($e->getMessage());
}