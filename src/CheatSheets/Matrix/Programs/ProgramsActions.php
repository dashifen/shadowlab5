<?php

namespace Shadowlab\CheatSheets\Matrix\Programs;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;

/**
 * Class ProgramsActions
 * @package Shadowlab\CheatSheets\Matrix\Programs
 */
class ProgramsActions extends AbstractAction {
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "programs";
	}

	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "program";
	}

	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "programs";
	}

	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "program_id";
	}

	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(
		SearchbarInterface $searchbar,
		PayloadInterface $payload
	): SearchbarInterface {
		$searchbar->addSearch("Program", "program");
		$searchbar->addReset();
		return $searchbar;
	}

}