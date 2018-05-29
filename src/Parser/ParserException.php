<?php

namespace Shadowlab\Parser;

use Dashifen\Exception\Exception;

class ParserException extends Exception {
	public const FILE_NOT_FOUND = 1;
	public const BAD_XML = 2;
}