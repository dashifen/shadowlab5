<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Exception\Exception;

class ActionException extends Exception {
	public const UNKNOWN_ACTION = 1;
	public const UNKNOWN_ACTION_HANDLER = 2;
	public const INVALID_RECORD_ID = 3;
}
