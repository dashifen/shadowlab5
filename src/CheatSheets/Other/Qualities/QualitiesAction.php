<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use ReCaptcha\Response;
use Shadowlab\Framework\Action\ShadowlabAction;
use Shadowlab\Framework\AddOns\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;


class QualitiesAction extends ShadowlabAction {
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
	
	protected function update(): ResponseInterface {
		
		// an update is a two step process:  first we read information that
		// we're going to update, then we save the changes to that data in
		// the database.  we can tell the difference based on the http verb
		// used to get here.
		
		$method = $this->request->getServerVar("REQUEST_METHOD") !== "POST"
			? "getDataToUpdate"
			: "savePostedData";
		
		return $this->{$method}();
	}
	
	protected function getDataToUpdate(): ResponseInterface {
		$payload = $this->domain->update(["quality_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"title"        => "Edit " . $payload->getDatum("title"),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
				"plural"       => "qualities",
				"singular"     => "quality",
				"errors"       => "",
			]);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
	}
	
	protected function savePostedData(): ResponseInterface {
		$payload = $this->domain->update(["posted" => $this->request->getPost()]);
		
		if ($payload->getSuccess()) {
			$data = $payload->getData();
			
			// we want to add some additional information necessary for
			// our view.  then, we slightly alter the title for our page
			// and send it all on its way.
			
			$data = array_merge($data, [
				"item"     => $data["title"],
				"plural"   => "qualities",
				"singular" => "quality",
				"success"  => true,
			]);
			
			$data["title"] .= " Saved";
			$this->handleSuccess($data);
		} else {
			
			// if we encountered errors when validating our data before
			// putting it back into the database, we end up here.  we send
			// back the same information as we do when we first present the
			// form, but
			
			$this->handleError([
				"title"        => "Unable to Save Changes",
				"posted"       => $payload->getDatum("posted"),
				"errors"       => $payload->getDatum("error"),
				"form"         => $this->getForm($payload),
				"plural"       => "qualities",
				"singular"     => "quality",
				"instructions" => "We were unable to save the changes you made
					to this information in the database.  Use the error messages
					below and fix the problem(s) we encountered.  When you're
					ready, click the button to continue.  If this problem
					persists, email Dash.  It probably means he messed up the
					code somehow.",
			]);
		}
		
		return $this->response;
	}
}
