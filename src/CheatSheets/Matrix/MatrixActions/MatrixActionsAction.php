<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;

class MatrixActionsAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		$searchbar->addSearch("Matrix Action", "matrix-action");
		$searchbar->addReset();
		return $searchbar;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "matrix action";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "matrix actions";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "matrix_actions";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "matrix_action_id";
	}
	
}
