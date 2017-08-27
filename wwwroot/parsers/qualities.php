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
$xml = new SimpleXMLElement(file_get_contents("data/qualities.xml"));
$books = $db->getMap("SELECT abbreviation, book_id FROM books");

$freakish = [
	"360-degree eyesight",
	"beak",
	"camouflage",
	"functional tail",
	"larger tusks",
	"low-light vision",
	"proboscis",
	"satyr legs",
	"shiva arms",
	"cephalopod skull",
	"cyclopean eye",
	"deformity",
	"feathers",
	"insectoid features",
	"neoteny",
	"scales",
	"third eye",
	"vestigial tail",
];


$isFreakish = function(string $quality) use ($freakish): string {
	$quality = strtolower($quality);
	foreach ($freakish as $freakishQuality) {
		if (strpos($quality, $freakishQuality) !== false) {
			return "Y";
		}
	}
	
	return "N";
};

foreach ($xml->qualities->quality as $quality) {
	extract((array)$quality);
	
	/**
	 * @var string $id
	 * @var string $name
	 * @var string $karma
	 * @var string $category
	 * @var string $source
	 * @var string $page
	 */
	
	if (!is_numeric($karma)) {
		
		// if our $karma cost is not numeric, then it's usually a range.
		// thus we can look for two numbers separated by some sort of non-
		// numeric character(s) and determine a minimum and maximum cost.
		
		if (!preg_match("/(\d+)\D+(\d+)/", $karma, $matches)) {
			echo "Could not update: $name<br>";
			continue;
		} else {
			$minimum = $matches[1];
			$maximum = $matches[2];
		}
	} else {
		$minimum = $karma;
		$maximum = "NULL";
	}
	
	// since the metagenetic flag is only set for those qualities
	// that require it, we can't rely on the extraction above to get
	// things right; if a prior quality was metagenetic, the flag may
	// still be set here.
	
	$metagenetic = isset($quality->metagenetic) ? "Y" : "N";
	
	$data = [
		"minimum"     => $minimum,
		"maximum"     => $maximum !== "NULL" ? $maximum : PDO::PARAM_NULL,
		"metagenetic" => $metagenetic,
		"freakish"    => $isFreakish($name),
		"book_id"     => $books[$source],
		"page"        => $page,
	];
	
	$quality = ["quality" => $name];
	$key = ["guid" => strtoupper($id)];
	$db->upsert("qualities", array_merge($quality, $data, $key), $data);
}


$db->runQuery("UPDATE qualities SET freakish = 'N'
	WHERE quality IN ('Low-Light Vision (Changeling)', 'Low-Light Vision')");

$db->runQuery("UPDATE qualities SET maximum = NULL WHERE maximum = 0");
