<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar;

class SpellsAction extends AbstractAction {
	protected function read(): ResponseInterface {
		
		// the optional parameter for a spell is the sanitized version of
		// a spells name (e.g. acid-stream for Acid Stream).  we'll pass it
		// to the domain and it'll know what to do regardless of whether we
		// have one or not.
		
		$payload = $this->domain->read(["spell_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"searchbar"    => $this->getSearchbar($payload),
				"table"        => $payload->getDatum("spells"),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"nextId"       => $payload->getDatum("nextId"),
				"capabilities" => $this->request->getSessionVar("capabilities"),
				"plural"       => "spells",
				"singular"     => "spell",
				"caption"      => "",
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
		$searchbarHTML = "";
		
		if ($payload->getDatum("count") > 1) {
			
			// if we were selecting multiple spells, then we need to make
			// the collection view's searchbar.  we can do so as follows,
			// utilizing that object's parse method.
			
			/** @var Searchbar $searchbar */
			
			$searchbar = $this->container->get("searchbar");
			$searchbar = $this->constructSearchbar($searchbar, $payload);
			$searchbarHTML = $searchbar->getBar();
		}
		
		return $searchbarHTML;
	}
	
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function constructSearchbar(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		
		// first, we need to get the tags, books, and categories out of the
		// $payload data so that we can add filters for these data.  then, we
		// can use that information to add fields to our $searchbar along with
		// other criteria that doesn't rely on our $payload.
		
		$spells = $payload->getDatum("original-spells");
		list($tags, $books, $categories) = $this->collectFilterOptions($spells);
		
		/** @var Searchbar $searchbar */
		
		$searchbar->addSearch("Spells", "spell");
		$searchbar->addFilter("Spell Categories", "spell-category", $categories, "", "All Spell Categories");
		$searchbar->addFilter("Spell Tags", "spell-tags", $tags, "", "All Spell Tags");
		$searchbar->addFilter("Books", "book", $books, "", "All Books");
		$searchbar->addReset();
		
		return $searchbar;
	}
	
	/**
	 * @param array $spells
	 *
	 * @return array
	 */
	protected function collectFilterOptions(array $spells): array {
		$lists = $this->getSpellDataLists($spells);
		
		// the getSpellDataLists() ensures that our lists are unique, but
		// they're unordered.  so, we'll want to sort them all and return
		// them to the calling scope.
		
		foreach ($lists as &$list) {
			asort($list);
		}
		
		return $lists;
	}
	
	/**
	 * @param array $spells
	 *
	 * @return array
	 */
	protected function getSpellDataLists(array $spells): array {
		$tags = $books = $categories = [];
		foreach ($spells as $i => $spell) {
			
			// we can add books and categories as follows because they have a
			// one-to-one relationship with spells.  tags are harder because
			// they're one-to-many.  we'll handle them in the next method.
			
			$books[$spell["book_id"]] = json_encode([
				"title" => $spell["book"],
				"text"  => $spell["abbr"],
			]);
			
			$categories[$spell["spell_category_id"]] = $spell["spell_category"];
			$tags += $this->getSpellTags($spell);
		}
		
		return [$tags, $books, $categories];
	}
	
	/**
	 * @param array $spell
	 *
	 * @return array
	 */
	protected function getSpellTags(array $spell): array {
		
		// tags have a one-to-many relationship with our $spell.  the IDs
		// for our tags are separated by underscores while the tags are
		// comma separated.  both are selected from the database in the
		// same order.  so, we can explode (and filter out blanks) each of
		// our strings and then use array_combine() to return a map of
		// tag IDs to tag names as follows:
		
		$x = array_filter(explode("_", $spell["spell_tags_ids"]));
		$y = array_filter(explode(", ", $spell["spell_tags"]));
		return array_combine($x, $y);
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function update(): ResponseInterface {
		
		// like our read action -- which can either read a single record
		// or a collection -- our update behavior is split in two as well.
		// if we do not have posted data as a part of our request, then
		// we actually want to read the old data out of the database and
		// send back a form.  otherwise, we update the database with the
		// data the visitor changed.
		
		$method = $this->request->getServerVar("REQUEST_METHOD") !== "POST"
			? "getDataToUpdate"
			: "savePostedData";
		
		return $this->{$method}();
	}
	
	/**
	 * @return ResponseInterface
	 */
	protected function getDataToUpdate(): ResponseInterface {
		
		// when getting data to update, we pass our record ID over to the
		// domain and it'll send back more information on what the visitor
		// has requested.
		
		$payload = $this->domain->update(["spell_id" => $this->recordId]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"title"        => "Edit " . $payload->getDatum("title"),
				"instructions" => $payload->getDatum("instructions", ""),
				"form"         => $this->getForm($payload),
				"plural"       => "spells",
				"singular"     => "spell",
				"errors"       => "",
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
				"plural"   => "spells",
				"singular" => "spell",
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
