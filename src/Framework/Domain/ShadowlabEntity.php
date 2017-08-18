<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Entity\AbstractEntity;

/**
 * Class Entity
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
