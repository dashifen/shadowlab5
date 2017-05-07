<?php

$handlers = [
	'Shadowlab\User\Login'  => ["GET" => "!/", "POST" => "!/authenticate"],
	'Shadowlab\CheatSheets' => ["GET" => "/cheat-sheets"],
	
	
	// Other Sheets
	
	'Shadowlab\CheatSheets\Other\Books' => ["GET" => "/cheat-sheets/other/books"],

];

$objects = [];
foreach ($handlers as $handler => $routes) {
	$parts = explode('\\', $handler);
	$handler = $handler . '\\' . array_pop($parts);
	
	$temp = new stdClass();
	
	$temp->routes = $routes;
	$temp->entity = $handler . "Entity";
	$temp->action = $handler . "Action";
	$temp->domain = $handler . "Domain";
	$temp->response = $handler . "Response";
	$temp->validator = $handler . "Validator";
	$temp->transformer = $handler . "Transformer";
	
	$objects[] = $temp;
}

return $objects;
