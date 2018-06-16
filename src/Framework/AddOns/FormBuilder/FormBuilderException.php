<?php

namespace Shadowlab\Framework\AddOns\FormBuilder;

use Dashifen\Form\Builder\FormBuilderException as ParentException;

class FormBuilderException extends ParentException {
	
	// our parent's exception already defines constants for MISSING_LEGENDS
	// and MISSING_FIELD_TYPE as numbers 1 and 2.  so, we start our extended
	// constants at 3.
	
	public const UNKNOWN_SCHEMA = 3;
	public const UNKNOWN_VALUES = 4;
	public const UNDEFINED_VALUES = 5;
	public const UNKNOWN_URL = 6;
}
