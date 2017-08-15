<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;

class QualitiesAction extends AbstractAction {
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbarHTML = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// when we're displaying more than one quality, we want to
			// construct a searchbar for our screen as follows.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->get("searchbar");
			
			$filterOptions = [
				"negative" => "Negative Qualities",
				"positive" => "Positive Qualities",
			];
			
			$searchbar->addSearch("Qualities", "quality");
			$searchbar->addFilter("Cost", "cost", $filterOptions, "", "Positive &amp; Negative");
			$searchbar->addToggle("Metagenetic", "metagenetic");
			$searchbar->addToggle("Freakish", "freakish");
			$searchbar->addReset();
			
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "quality";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "qualities";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "qualities";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "quality_id";
	}
}
