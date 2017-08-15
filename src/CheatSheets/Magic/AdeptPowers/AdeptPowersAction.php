<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;

class AdeptPowersAction extends AbstractAction {
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbarHTML = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// if we're showing more than one power on this screen, then
			// it's worth it to provide a searchbar.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->get("searchbar");
			$searchbar->addSearch("Powers", "power");
			$searchbar->addReset();
			
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "adept power";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "adept powers";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "adept_powers";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "adept_power_id";
	}
	
}
