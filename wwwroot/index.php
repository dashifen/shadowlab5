<?php
require("../vendor/autoload.php");

use League\BooBoo\Runner;
use League\BooBoo\Formatter\HtmlTableFormatter;
use Aura\Di\ContainerBuilder;

$runner = new Runner();
$runner->pushFormatter(new HtmlTableFormatter());
$runner->register();

$cb = new ContainerBuilder();
$di = $cb->newConfiguredInstance([
	'Shadowlab\Config\Database',
	
	
	
	'Shadowlab\Config\Dispatcher',
]);
