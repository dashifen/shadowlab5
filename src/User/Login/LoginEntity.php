<?php

namespace Shadowlab\User\Login;

use Dashifen\Domain\Entity\AbstractEntity;

class LoginEntity extends AbstractEntity {
	
	// at this time, there's no purpose to a LoginEntity.  but, for
	// completeness purposes, and to ensure that our Container configuration
	// can be the same for all of our route handling objects, we'll make
	// one anyway.
	
	public function validate(): bool {
		
		// since it's nice to be validated, we'll let this otherwise
		// useless object think highly of itself:
		
		return true;
	}
}
