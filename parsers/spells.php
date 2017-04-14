<?php
require("../vendor/autoload.php");

use League\BooBoo\Runner;
use League\BooBoo\Formatter\HtmlTableFormatter;
use Shadowlab\Database\ShadowlabDatabase;
use Dashifen\Database\DatabaseException;

$xml = new SimpleXMLElement(file_get_contents("data/spells.xml"));

