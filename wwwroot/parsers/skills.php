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
$xml = new SimpleXMLElement(file_get_contents("data/skills.xml"));
$books = $db->getMap("SELECT abbreviation, book_id FROM books");

function groupNameConvert(SimpleXMLElement $skillGroup): string {
	return strtolower((string)$skillGroup);
}

function categoryNameConvert(SimpleXMLElement $category): string {
	return strtolower(str_replace(" Active", "", (string)$category));
}

foreach ($xml->skillgroups->name as $skillGroup) {
	$key = ["skill_group" => groupNameConvert($skillGroup)];
	$skillGroupId = $db->getVar("SELECT skill_group_id FROM skill_groups
		WHERE skill_group = :skill_group", $key);
	
	if (!is_numeric($skillGroupId)) {
		$db->insert("skill_groups", $key);
	}
}

foreach ($xml->categories->category as $category) {
	/** @var SimpleXMLElement $category */
	
	$type = (string)($category->attributes()["type"]);
	$category = categoryNameConvert($category);
	$key = ["skill_category" => $category, "skill_category_type" => $type];
	$skillCategoryId = $db->getVar("SELECT skill_category_id
		FROM skills_categories WHERE skill_category = :skill_category
		AND skill_category_type = :skill_category_type", $key);
	
	if (!is_numeric($skillCategoryId)) {
		$db->insert("skills_categories", $key);
	}
}

/*
(
    [0] => id
    [1] => name
    [2] => attribute
    [3] => category
    [4] => default
    [5] => skillgroup
    [6] => specs
    [7] => source
    [8] => page
    [293] => exotic
)
 */

$groups = $db->getMap("SELECT skill_group, skill_group_id FROM skill_groups");
$categories = $db->getMap("SELECT skill_category, skill_category_id FROM skills_categories");


foreach ($xml->skills->skill as $skill) {
	unset($id, $name, $category, $default, $skillgroup,
		$specs, $source, $page, $exotic);
	
	/**
	 * @var string $id
	 * @var string $name
	 * @var string $default
	 * @var string $skillgroup
	 * @var array  $specs
	 * @var string $source
	 * @var string $page
	 * @var string $exotic
	 */
	
	extract((array)$skill);
	
	$key = ["guid" => strtolower($id)];
	$name = ["skill" => $name];
	$data = [
		"skill_category_id" => $categories[categoryNameConvert($skill->category)],
		"can_default"       => $default === "Yes" ? "Y" : "N",
		"is_exotic"         => $default === "Yes" ? "Y" : "N",
		"book_id"           => $books[$source],
		"page"              => $page,
	];
	
	// chummer's pseudo-magical skill category is dumb.  we'll just put
	// those into the magical category as follows.
	
	if ($data["skill_category_id"] == 5) {
		$data["skill_category_id"] = 4;
	}
	
	if (isset($skillgroup) && strlen($skillgroup) > 0) {
		$skillgroup = $groups[groupNameConvert($skill->skillgroup)];
		$data["skill_group_id"] = $skillgroup;
	}
	
	$db->upsert("skills", array_merge($key, $name, $data), $data);
	$skillId = $db->getVar("SELECT skill_id FROM skills WHERE guid = :guid", $key);
	$db->delete("skills_specializations", ["skill_id" => $skillId]);
	
	if (sizeof($skill->specs->children()) > 0) {
		
		$insertions = [];
		foreach ($skill->specs->children() as $spec) {
			$insertions[] = [
				"skill_id"       => $skillId,
				"specialization" => (string)$spec,
			];
		}
		
		$db->insert("skills_specializations", $insertions);
	}
}

echo "done";
