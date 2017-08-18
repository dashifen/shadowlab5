<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;

class CheatSheetsAction extends AbstractAction {
	protected function read(): ResponseInterface {
		$payload = $this->domain->read(["recordId" => $this->recordId]);
		
		// there's actually no failure case here.  either we had a record ID
		// and get the menu for that sheet type, or we didn't and we use the
		// whole menu.  so, we can just make some $data for our response and
		// call handleSuccess().
		
		$data = ["title" => "ShadowLab Cheat Sheets"];
		$sheetTypeMenu = $payload->getDatum("sheetTypeMenu");
		if (!empty($sheetTypeMenu)) {
			$data["sheetTypeMenu"] = $sheetTypeMenu;
		}
		
		$this->handleSuccess($data);
		return $this->response;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		return $searchbar;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "cheat sheet";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "cheat sheets";
	}
	
	protected function getTable(): string {
		return "cheat_sheets";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "sheet_id";
	}
}
