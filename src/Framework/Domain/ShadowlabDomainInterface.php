<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\DomainInterface;

/**
 * Interface ShadowlabDomainInterface
 *
 * @package Shadowlab\Framework\Domain
 */
interface ShadowlabDomainInterface extends DomainInterface {
	/**
	 * @param string $sheetType
	 *
	 * @return int
	 */
	public function getSheetTypeId(string $sheetType): int;
	
	/**
	 * @return string
	 */
	public function getShadowlabMenu(): string;
}
