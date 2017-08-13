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
$xml = new SimpleXMLElement(file_get_contents("data/powers.xml"));
$ways = $db->getMap("SELECT quality, quality_id FROM adept_ways_view");
$books = $db->getMap("SELECT abbr, book_id FROM books");

$actions = [];
$allProperties = [];
foreach ($xml->powers->power as $i => $power) {
	
	// these two properties are optional, so we'll want to unset them
	// every time so that we always know that we're working with this
	// power's version of them.
	
	unset($maxlevels, $adeptwayrequires);
	extract((array)$power);
	
	/**
	 * @var string $id
	 * @var string $name
	 * @var string $points
	 * @var string $levels
	 * @var string $source
	 * @var string $page
	 * @var string $action
	 * @var mixed  $adeptwayrequires
	 * @var string $maxlevels
	 */
	
	$data = [
		"adept_power" => $name,
		"cost"        => $points,
		"levels"      => $levels === "true" ? "Y" : "N",
		"action"      => strtolower($action),
		"book_id"     => $books[$source],
		"page"        => $page,
	];
	
	$key = [
		"guid" => strtolower($id),
	];
	
	if (is_numeric($maxlevels ?? "")) {
		$data["maximum_levels"] = $maxlevels;
	}
	
	$db->upsert("adept_powers", array_merge($data, $key), $data);
	
	if (isset($adeptwayrequires)) {
		$adept_power_id = $db->getVar("SELECT adept_power_id
			FROM adept_powers WHERE guid = :guid", $key);
		
		$insertions = [];
		foreach ($adeptwayrequires->required->oneof->quality as $quality) {
			$quality = (string) $quality;
			
			$insertions[] = [
				"adept_power_id" => $adept_power_id,
				"quality_id" => $quality_id = $ways[$quality]
			];
		}
		
		$db->delete("adept_powers_ways", ["adept_power_id" => $adept_power_id]);
		$db->insert("adept_powers_ways", $insertions);
	}
}
