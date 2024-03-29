<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;
use Dashifen\Searchbar\SearchbarException;

class SpellsAction extends AbstractAction {
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "spell";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "spells";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "spells";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "spell_id";
	}

	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 * @throws SearchbarException
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		
		// first, we need to get the tags, books, and categories out of the
		// $payload data so that we can add filters for these data.  then, we
		// can use that information to add fields to our $searchbar along with
		// other criteria that doesn't rely on our $payload.
		
		$spells = $payload->getDatum("original-records");
		list($tags, $categories) = $this->collectFilterOptions($spells);
		
		$searchbar->addSearch("Spells", "spell");
		$searchbar->addFilter("Spell Categories", "spell-category", $categories, "", "All Spell Categories");
		$searchbar->addFilter("Spell Tags", "spell-tags", $tags, "", "All Spell Tags");
		$searchbar->addFilter("Books", "book", $this->getBookOptions($payload), "", "All Books");
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
				"text"  => $spell["abbreviation"],
				"title" => $spell["book"],
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
}
