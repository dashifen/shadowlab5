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
	$xml = file_get_contents("data/programs.xml");
	$xml = new SimpleXMLElement($xml);

	foreach ($xml->categories->category as $category) {
		try {

			// critter type is a unique key, so we can just insert
			// and if the database blocks us, it'll throw an exception.
			// we catch it below and simple do nothing with it.

			$db->insert("programs_types", [
				"program_type" => $category,
			]);
		} catch (DatabaseException $e) {
			continue;
		}
	}

	$books = $db->getMap("SELECT abbreviation, book_id FROM books");
	$types = $db->getMap("SELECT program_type, program_type_id FROM programs_types");

	foreach ($xml->programs->program as $program) {
		$data = [
			"availability"    => (string) $program->avail,
			"max_rating"      => (string) ($program->rating ?? 0),
			"program_type_id" => $types[(string) $program->category],
			"book_id"         => $books[(string) $program->source],
			"page"            => (int) $program->page,
		];

		$programName["program"] = (string) $program->name;
		$guid["guid"] = strtolower((string) $program->id);
		$db->upsert("programs", array_merge($programName, $data, $guid), $data);
	}

} catch (DatabaseException $e) {
	die($e->getMessage());
}