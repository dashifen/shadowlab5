<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\AbstractValidator;

class QualitiesValidator extends AbstractValidator {
	/**
	 * @param array $posted
	 * @param array $schema
	 *
	 * @return bool
	 */
	protected function checkForOtherErrors(array $posted, array $schema): bool {
		return false;
	}
}
