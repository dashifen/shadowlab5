<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;

class QualitiesAction extends AbstractAction {
	protected function read(): ResponseInterface {
		$payload = $this->domain->read(["quality_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"table"        => $payload->getDatum("qualities"),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"nextId"       => $payload->getDatum("nextId"),
				"searchbar"    => $this->getSearchbar($payload),
				"capabilities" => $this->request->getSessionVar("capabilities"),
				"plural"       => "qualities",
				"singular"     => "quality",
				"caption"      => "",
			]);
		} else {
			$this->handleFailure([
				"noun"  => $payload->getDatum("count") > 1 ? "qualities" : "quality",
				"title" => "Perception Failed",
			]);
		}
		
		return $this->response;
	}
}
