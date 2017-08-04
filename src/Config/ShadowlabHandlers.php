<?php

$handlers = [
	'Shadowlab\User\Login'  => ["GET" => "!/", "POST" => "!/authenticate"],
	'Shadowlab\CheatSheets' => ["GET" => "/cheat-sheets"],
	
	// Magic Sheets
	
	'Shadowlab\CheatSheets\Magic\Spells' => ["BOTH" => "/cheat-sheets/magic/spells"],
	
	
	// Other Sheets
	
	'Shadowlab\CheatSheets\Other\Books' => ["BOTH" => "/cheat-sheets/other/books"],

];

$objects = [];
foreach ($handlers as $handler => $routes) {
	$parts = explode('\\', $handler);
	$handler = $handler . '\\' . array_pop($parts);
	
	if (isset($routes["BOTH"])) {
		$routes = [
			"GET"  => $routes["BOTH"],
			"POST" => $routes["BOTH"],
		];
	}
	
	$temp = new stdClass();
	$temp->routes = $routes;
	
	// handlers always have unique actions and responses - e.g. the action
	// that displays information about a collection or record is different
	// from the one that edits that collection or record.
	
	$temp->domain = $handler . "Domain";
	$temp->validator = $handler . "Validator";
	$temp->transformer = $handler . "Transformer";
	$temp->response = $handler . "Response";
	$temp->action = $handler . "Action";
	
	// we're not sure that we need entities, but well leave the information
	// about a specific entity for this handler set here.  that way, if we
	// need to add them later, we'll not have to edit this file.
	
	$temp->entity = $handler . "Entity";
	$objects[] = $temp;
}

return $objects;
