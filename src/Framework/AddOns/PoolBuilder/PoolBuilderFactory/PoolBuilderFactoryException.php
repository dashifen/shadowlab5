<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory;

use Dashifen\Exception\Exception;

class PoolBuilderFactoryException extends Exception {
	public const INVALID_STRATEGY = 1;
	public const INVALID_CONSTITUENTS = 2;
	public const INVALID_BOTH = 3;
}