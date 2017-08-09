<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar;

class BooksAction extends AbstractAction {
	/**
	 * @return ResponseInterface
	 */
	protected function read(): ResponseInterface {
		$payload = $this->domain->read(["book_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"table"        => $payload->getDatum("books"),
				"searchbar"    => $this->getSearchbar($payload),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"capabilities" => $this->request->getSessionVar("capabilities"),
				"caption"      => "The following are the SR books in this application.
					They're not all the published books for SR5; only those with
					quantifiable rules and stats are here.  Some, mostly the German
					content, are not included, i.e. you won't find their data
					elsewhere in the Shadowlab.",
			]);
		} else {
			$this->handleFailure([
				"noun"  => empty($parameter) ? "books" : "book",
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
			
			// if we're displaying more than one book, then we want a
			// searchbar to help the visitor find the one they're looking
			// for.
			
			/** @var SearchbarInterface $searchbar */
			
			$searchbar = $this->container->get("searchbar");
			$searchbar = $this->constructSearchbar($searchbar);
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 *
	 * @return SearchbarInterface
	 */
	protected function constructSearchbar(SearchbarInterface $searchbar): SearchbarInterface {
		
		// our searchbar should offer a way to search within a book's
		// name as well as a way to filter by those books which are or
		// are not included in the rest of the Shadowlab app.  luckily,
		// the construction of this bar doesn't require sifting through
		// our data since we know the only values for our included
		// column are "included" and "excluded."
		
		$options = [
			"included" => "Included",
			"excluded" => "Excluded",
		];
		
		/** @var Searchbar $searchbar */
		
		$searchbar->addRow();
		$searchbar->addSearch("Books", "book");
		$searchbar->addFilter("Included", "included", $options, "",
			"Both included and excluded");
		
		$searchbar->addReset();
		return $searchbar;
	}
	
	protected function update(): ResponseInterface {
		
		// an update is a two step process:  get data to update and
		// then we update the database with the changes to it.  which
		// step we're on when we're here is based on the existence of
		// posted data.
		
		$method = $this->request->getServerVar("REQUEST_METHOD") !== "POST"
			? "getDataToUpdate"
			: "savePostedData";
		
		return $this->{$method}();
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function getDataToUpdate(): ResponseInterface {
		
		// if we're getting data to update, then this is actually
		// a special sort of read from the database.  the domain can
		// tell the difference between looking up old data and patching
		// new data because our parameter here doesn't include any new
		// data.
		
		$payload = $this->domain->update(["book_id" => $this->recordId]);
		
		// now, our $payload can tell us how to build a form for this
		// information on the client side similar to how we build a
		// searchbar when reading multiple books above.
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"title"        => "Edit " . $payload->getDatum("title"),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
				"plural"       => "books",
				"singular"     => "book",
			]);
		} else {
			$this->handleFailure([]);
		}
		
		return $this->response;
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function savePostedData(): ResponseInterface {
		$payload = $this->domain->update(["posted" => $this->request->getPost()]);
		
		if ($payload->getSuccess()) {
			$data = $payload->getData();
			
			// we want to add some additional information necessary for
			// our view.  then, we slightly alter the title for our page
			// and send it all on its way.
			
			$data = array_merge($data, [
				"item"     => $data["title"],
				"plural"   => "books",
				"singular" => "book",
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
				"plural"       => "books",
				"singular"     => "book",
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
