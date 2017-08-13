<?php

namespace Shadowlab\CheatSheets;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Dashifen\Response\ResponseInterface;

class CheatSheetsAction extends AbstractAction {
	public function execute(array $parameter = []): ResponseInterface {
		
		// the cheat sheet action is different from the others.  so,
		// we're just going to overwrite the default execute() method
		// to do exactly what we need it to do here.
		
		$sheet_type = sizeof($parameter) !== 0 ? $parameter[0] : "";
		$payload = $this->domain->read(["sheet_type" => $sheet_type]);
		
		if ($payload->getSuccess()) {
			
			// a successful payload provides us with information about
			// which sheets to display on-screen.  that, the title, and
			// our menu is all we need here.
			
			$this->handleSuccess([
				"sheets" => $payload->getDatum("sheets"),
				"title"  => trim(ucfirst($sheet_type) . " Cheat Sheets"),
				"parameter" => $parameter
			]);
		} else {
			
			// other than success, the only thing that could have happened
			// is that our $parameter specified a type of sheet that doesn't
			// exist.  this feels more like a 404 situation rather than
			// failure or an error.
			
			$this->response->handleNotFound();
		}
		
		return $this->response;
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return string
	 */
	protected function getSearchbar(PayloadInterface $payload): string {
		
		// our cheat sheets don't get a searchbar at this time, so we
		// just return the empty string.
		
		return "";
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
