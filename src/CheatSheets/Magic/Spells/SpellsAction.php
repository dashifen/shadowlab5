<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;

class SpellsAction extends AbstractAction {
	public function execute(array $parameter = []): ResponseInterface {
		
		// the optional parameter for a spell is the sanitized version of
		// a spells name (e.g. acid-stream for Acid Stream).  we'll pass it
		// to the domain and it'll know what to do regardless of whether we
		// have one or not.
		
		$payload = $this->domain->read(["spell_id" => $parameter]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"searchbar" => $this->getSearchbar($payload),
				"table"     => $payload->getDatum("spells"),
				"title"     => $payload->getDatum("title"),
				"count"     => $payload->getDatum("count"),
				"caption"   => "",
			]);
		} else {
			$this->handleError([
				"noun"  => $payload->getDatum("count") > 1 ? "spells" : "spell",
				"title" => "Perception Failed",
			]);
		}
		
		return $this->response;
	}
	
	protected function getSearchbar(PayloadInterface $payload): string {
		$searchbar = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// if we were selecting multiple spells, then we need to make
			// the collection view's searchbar.  we can do so as follows,
			// utilizing that object's parse method.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->newInstance('Shadowlab\Framework\AddOns\Searchbar');
			$searchbar = $searchbar->parse($payload->getDatum("spells")["searchbar"]);
		}
		
		return $searchbar;
	}
}
