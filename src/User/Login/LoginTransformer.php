<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Domain\Transformer;

class LoginTransformer extends Transformer {
	
	// the login behavior doesn't actually have to transform any of
	// its data.  but, in order to ensure that our route handling config
	// process can be the same for all of our handler sets, we'll create
	// this object rather than hack an exception into that process.
	
}
