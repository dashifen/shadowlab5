<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar;

class SpellsAction extends AbstractAction {
	public function execute(array $parameter = []): ResponseInterface {
		
		// the optional parameter for a spell is the sanitized version of
		// a spells name (e.g. acid-stream for Acid Stream).  we'll pass it
		// to the domain and it'll know what to do regardless of whether we
		// have one or not.
		
		$payload = $this->domain->read(["spell_id" => $parameter]);
		
		if ($payload->getSuccess()) {
			$this->handleSuccess([
				"searchbar"    => $this->getSearchbar($payload),
				"table"        => $payload->getDatum("spells"),
				"title"        => $payload->getDatum("title"),
				"count"        => $payload->getDatum("count"),
				"capabilities" => $this->request->getSessionVar("capabilities"),
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
		
		// our tags, books, and categories data all lay within our $spells
		// array.  we'll iterate over that and get the information that we
		// need from it.  notice that during our original iteration, we don't
		// worry about testing for uniqueness; we'll handle that afterward.
		
		$tags = [];
		$books = [];
		$categories = [];
		foreach ($spells as $i => $spell) {
			$books[$spell["book_id"]] = $spell["abbr"];
			$categories[$spell["spell_category_id"]] = $spell["spell_category"];
			
			// those two were easy, but our tags are harder because it's a
			// one-to-many relationship between spells and tags.  so, we need
			// to explode our tag IDs and our tags and them combine them
			// a follows:
			
			$x = array_filter(explode("_", $spell["spell_tags_ids"]));
			$y = array_filter(explode(", ", $spell["spell_tags"]));
			
			// and, now that we have a $x and $y representing tag IDs and
			// the tags themselves, we can combine those and append the
			// result into $tags.
			
			$tags += array_combine($x, $y);
		}
		
		// since books and categories are added specifically by ID, those
		// lists are already unique, but they might not be sorted.  our tags,
		// though, are just appended over and over again and so there's
		// definitely duplicates therein.  here, we loop over them all and
		// tough up the data preparing it for the screen.
		
		$data = [
			"tags"       => $tags,
			"books"      => $books,
			"categories" => $categories,
		];
		
		foreach ($data as $i => &$datum) {
			if ($i === "categories") {
				$datum = array_unique($datum);
			}
			
			asort($datum);
		}
		
		// our calling scope will use list() to separate our three lists
		// into their own variables once more.  so, we only send back the
		// array_values();
		
		return array_values($data);
	}
}
