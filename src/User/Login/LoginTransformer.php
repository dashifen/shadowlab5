<?php

namespace Shadowlab\User\Login;

use Shadowlab\Framework\Domain\AbstractTransformer;

class LoginTransformer extends AbstractTransformer {
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function transformAll(array $powers): array {
		return $powers;
	}
	
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformOne(array $records): array {
		return $records;
	}
}
