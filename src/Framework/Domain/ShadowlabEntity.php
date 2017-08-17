<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Entity\AbstractEntity;

/**
 * Class Entity
 *
 * A default Entity definition for this app.  It simply indicates that
 * it's always valid.  Children that might think otherwise about themselves
 * can override this method as necessary.
 *
 * @package Shadowlab\Framework\Domain
 */
class ShadowlabEntity extends AbstractEntity {
	/**
	 * @return bool
	 */
	public function validate(): bool {
		return true;
	}
}
