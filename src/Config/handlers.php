<?php

$handlers = [
	'Shadowlab\User\Login' => [ "GET" => "!/", "POST" => "!/authenticate" ],
	
	
	
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
	$temp->response  = $handler . "Response";
	$temp->validator = $handler . "Validator";
	
	$objects[] = $temp;
}

return $objects;
