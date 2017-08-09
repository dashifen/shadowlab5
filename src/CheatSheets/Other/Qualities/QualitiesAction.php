<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;


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
}
