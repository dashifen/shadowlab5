<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Searchbar\SearchbarException;

class QualitiesAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 * @throws SearchbarException
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		$filterOptions = [
			"negative" => "Negative Qualities",
			"positive" => "Positive Qualities",
		];

		$searchbar->addSearch("Qualities", "quality");
		$searchbar->addFilter("Cost", "cost", $filterOptions, "", "Positive &amp; Negative");
		$searchbar->addFilter("Books", "book", $this->getBookOptions($payload), "", "All books");
		$searchbar->addToggle("Metagenetic", "metagenetic");
		$searchbar->addToggle("Freakish", "freakish");
		$searchbar->addReset();
		return $searchbar;
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
