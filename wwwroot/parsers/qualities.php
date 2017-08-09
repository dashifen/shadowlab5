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
$books = $db->getMap("SELECT abbr, book_id FROM books");

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
	
	$data = [
		"quality" => $name,
		"minimum" => $minimum,
		"maximum" => $maximum !== "NULL" ? $maximum : PDO::PARAM_NULL,
		"book_id" => $books[$source],
		"page"    => $page,
	];

	$key = ["guid" => strtoupper($id)];
	$db->upsert("qualities", array_merge($data, $key), $data);
}

$db->runQuery("UPDATE qualities SET maximum = NULL WHERE maximum = 0");
