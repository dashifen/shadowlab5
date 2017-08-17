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
	
	unset($maxlevels, $adeptwayrequires, $extrapointcost);
	extract((array)$power);
	
	/**
	 * @var string $id
	 * @var string $name
	 * @var float  $points
	 * @var string $levels
	 * @var string $source
	 * @var string $page
	 * @var string $action
	 * @var float  $extrapointcost
	 * @var mixed  $adeptwayrequires
	 * @var string $maxlevels
	 */
	
	if (empty($extrapointcost)) {
		$extrapointcost = 0;
	}
	
	$data = [
		"cost"        => ($points + $extrapointcost),
		"levels"      => $levels === "true" ? "Y" : "N",
		"action"      => strtolower($action),
		"book_id"     => $books[$source],
		"page"        => $page,
	];
	
	$adept_power = ["adept_power" => $name];
	$key = ["guid" => strtolower($id)];
	
	if ($levels === "true") {
		$data["cost_per_level"] = $points;
	}
	
	if (is_numeric($maxlevels ?? "")) {
		$data["maximum_levels"] = $maxlevels;
	}
	
	// the insert data has to include our name, key, and the rest of
	// our data.  but, for the updating information, we only use the
	// data.  this allows us to change some of the names of some powers
	// without having them overwritten when we parse things again next
	// time.
	
	$db->upsert("adept_powers", array_merge($adept_power, $data, $key), $data);
	
	if (isset($adeptwayrequires)) {
		$adept_power_id = $db->getVar("SELECT adept_power_id
			FROM adept_powers WHERE guid = :guid", $key);
		
		$insertions = [];
		foreach ($adeptwayrequires->required->oneof->quality as $quality) {
			$quality_id = $ways[(string)$quality];
			
			if (in_array($quality_id, [135, 138, 140])) {
				
				// these three ways (beast, magician, and spiritual
				// respectively) are handled in chummer differently than
				// the others due to their ability to "choose another power"
				// for a discount.  the other ways are fine, but for these
				// we'll use the following to see if we add this power/way
				// pair to the $insertions array.  note: since magician's way
				// folk choose anything except Improved Reflexes, they get
				// an empty array.
				
				if ($quality_id == 135) {
					$powers = [2, 30, 4, 38, 7, 16, 50, 20, 54, 25];
				} elseif ($quality_id == 140) {
					$powers = [2, 38, 39, 7, 16, 50, 20];
				} else {
					$powers = [];
				}
				
				if (!in_array($adept_power_id, $powers)) {
					
					// now, if the power we're currently looking at isn't
					// in the approved list, we'll just continue.
					
					continue;
				}
			}
			
			$insertions[] = [
				"adept_power_id" => $adept_power_id,
				"quality_id"     => $quality_id,
			];
		}
		
		$db->delete("adept_powers_ways", ["adept_power_id" => $adept_power_id]);
		
		if(sizeof($insertions) > 0) {
			$db->insert("adept_powers_ways", $insertions);
		}
	}
}

echo "done.";
