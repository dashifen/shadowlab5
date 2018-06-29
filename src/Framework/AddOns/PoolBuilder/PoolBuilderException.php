<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

use Dashifen\Exception\Exception;

class PoolBuilderException extends Exception {
	public const MISSING_DATA = 1;
	public const MISSING_OFFENSIVE_DATA = 2;
	public const MISSING_DEFENSIVE_DATA = 3;
	public const NON_NUMERIC_CONSTITUENT = 4;
	public const MISSING_CONSTITUENTS = 5;
}