<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Action\AbstractAction;
use Dashifen\Response\ResponseInterface;

class CheatSheetsAction extends AbstractAction {
	public function execute(string $parameter = ""): ResponseInterface {
		$payload = $this->domain->read(["sheet_type" => $parameter]);
		
		if ($payload->getSuccess()) {
			
			// a successful payload provides us with information about
			// which sheets to display on-screen.  that, the title, and
			// our menu is all we need here.
			
			$this->handleSuccess([
				"sheets" => $payload->getDatum("sheets"),
				"title"  => trim(ucfirst($parameter) . " Cheat Sheets"),
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
