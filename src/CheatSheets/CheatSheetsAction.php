<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Action\AbstractAction;
use Dashifen\Response\ResponseInterface;

class CheatSheetsAction extends AbstractAction {
	public function execute(array $parameter = []): ResponseInterface {
		
		// our parameter array should only have one item in it:  a type
		// for our cheat sheet display.
		
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
}
