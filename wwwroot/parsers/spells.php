<?php
require("../../vendor/autoload.php");

use Dashifen\Exceptionator\Exceptionator;
use Dashifen\Request\Request;
use Dashifen\Session\Session;
use Shadowlab\Framework\Database\Database;
use Zend\Diactoros\ServerRequestFactory;

$request = new Request(ServerRequestFactory::fromGlobals(), new Session());
$exceptionator = new Exceptionator($request);
$exceptionator->handleExceptions(true);
$exceptionator->handleErrors(true);

function debug(...$x) {
	$dumps = [];
	foreach ($x as $y) {
		$dumps[] = print_r($y, true);
	}
	
	echo "<pre>" . join("</pre><pre>", $dumps) . "</pre>";
}

$db = new Database();
$xml = new SimpleXMLElement(file_get_contents("data/spells.xml"));
$books = $db->getMap("SELECT abbr, book_id FROM books");

// there's a few steps to handle before we actually start inserting
// our spells.  the first thing we'll do is see if there's anything
// new to handle.  we'll start with categories because they're handled
// differently from the rest.

$categories = [];
foreach ($xml->categories->category as $category) {
	$categories[] = (string)$category;
}

// now, we'll want to get the information about tags, durations, etc.
// but this is all within the the XML, so we'll do an initial loop here
// to get at that information.

$tags = [];
$types = [];
$ranges = [];
$damages = [];
$durations = [];

foreach ($xml->spells->spell as $spell) {
	$tags = array_merge($tags, explode(", ", (string)$spell->descriptor));
	$types[] = (string)$spell->type;
	$ranges[] = (string)$spell->range;
	$damages[] = (string)$spell->damage;
	$durations[] = (string)$spell->duration;
}

$filter = function($x) {
	return !empty(trim($x));
};

$categories = array_filter(array_unique($categories), $filter);
$tags = array_filter(array_unique($tags), $filter);
$types = array_filter(array_unique($types), $filter);
$ranges = array_filter(array_unique($ranges), $filter);
$damages = array_filter(array_unique($damages), $filter);
$durations = array_filter(array_unique($durations), $filter);

// now that we have unique lists of our categories, tags, etc., we need to
// start putting them into the database.  categories and tags get their own
// tables, so we'll do them first.

$db_categories = $db->getCol("SELECT spell_category FROM spells_categories");
$new_categories = array_diff($categories, $db_categories);

foreach ($new_categories as $new_category) {
	$db->insert("spells_categories", ["spell_category" => $new_category]);
}

$category_map = $db->getMap("SELECT spell_category, spell_category_id FROM spells_categories");

$db_tags = $db->getCol("SELECT spell_tag FROM spells_tags");
$new_tags = array_diff($tags, $db_tags);

foreach ($new_tags as $tag) {
	$db->insert("spells_tags", ["spell_tag" => $tag]);
}

$tag_map = $db->getMap("SELECT spell_tag, spell_tag_id FROM spells_tags");

// now, for the remaining four lists, we need to be sure that the ENUM
// columns in the spells table contains the appropriate set of values
// based on what's in our lists.  if not, we need to update the structure
// of that table.

$lists = [
	"type"     => $types,
	"range"    => $ranges,
	"damage"   => $damages,
	"duration" => $durations,
];

$quit = false;
foreach ($lists as $list => $data) {
	
	// now, we want to be sure that the spells table can handle it.  each
	// of the lists here matches the name of a column in that table, and
	// we'll want to make sure that the ENUM values therein match our data.
	
	$enum_values = $db->getEnumValues("spells", $list);
	$difference = array_diff($data, $enum_values);
	if (sizeof($difference) !== 0) {
		echo "Must add the following to spells.$list:";
		debug($difference);
		$quit = true;
	}
}

if ($quit) {
	die();
}

// finally, we're ready to actually begin the inserting of data into
// the database.

$noIds = [];
$spells = $db->getCol("SELECT guid FROM spells");

foreach ($xml->spells->spell as $xmlSpell) {
	$guid = strtoupper((string)$xmlSpell->id);
	
	if (!empty($guid)) {
		$spell = [
			"spell_category_id" => $category_map[(string)$xmlSpell->category],
			"spell"             => (string)$xmlSpell->name,
			"type"              => (string)$xmlSpell->type,
			"range"             => (string)$xmlSpell->range,
			"damage"            => (string)$xmlSpell->damage,
			"duration"          => (string)$xmlSpell->duration,
			"drain_value"       => (string)$xmlSpell->dv,
			"book_id"           => $books[(string)$xmlSpell->source],
			"page"              => (string)$xmlSpell->page,
		];
		
		if ($spell["drain_value"] === "-4") {
			$spell["drain_value"] = "F-4";
		}
		
		$key = ["guid" => $guid];
		$db->upsert("spells", array_merge($spell, $key), $spell);
		$spell_id = $db->getVar("SELECT spell_id FROM spells WHERE guid=:guid", $key);
		
		// now that we have a spell id for this spell, we're going to handle
		// its tags.  first, we want to get a list of the tags that this spell
		// should have from the XML.  we'll extract them using a similar line of
		// code as we did above.  then we convert them to IDs and compare them
		// to the tags already in the database (if any) for this spell.
		
		$tags = explode(", ", (string)$xmlSpell->descriptor);
		$tags = array_filter(array_unique($tags), $filter);
		
		foreach ($tags as $i => $tag) {
			$tags[$i] = $tag_map[$tag];
		}
		
		$db_tags = $db->getCol("SELECT spell_tag_id FROM spells_spell_tags WHERE spell_id=:spell_id", ["spell_id" => $spell_id]);
		$tags_to_add = array_diff($tags, $db_tags);        // tags in the XML that aren't in the database
		$tags_to_del = array_diff($db_tags, $tags);        // tags in the database that aren't in the XML
		
		if (sizeof($tags_to_add) > 0) {
			foreach ($tags_to_add as $i => $tag_id) {
				$db->insert("spells_spell_tags", [
					"spell_id"     => $spell_id,
					"spell_tag_id" => $tag_id,
				]);
			}
		}
		
		if (sizeof($tags_to_del) > 0) {
			$sql = "DELETE FROM spells_spell_tags WHERE spell_id=:spell_id AND spell_tag_id IN (:tags)";
			$db->runQuery($sql, ["spell_id" => $spell_id, "tags" => $tags_to_del]);
		}
	} else {
		$noIds[] = sprintf("%s (%s, %s)",
			(string)$xmlSpell->name,
			(string)$xmlSpell->source,
			(string)$xmlSpell->page);
	}
}

echo "No ID:";
debug($noIds);
