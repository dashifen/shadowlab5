<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Domain\Entity;

class CheatSheetsEntity extends Entity {
	
	// our cheat sheet display doesn't really have any data to pass
	// around at this time.  but, in order to avoid having an exception
	// in our DI Container configuration, we'll create the object even
	// though we're not doing anything with it at the moment.
	
}
