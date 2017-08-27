<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Domain\AbstractTransformer;

class SpellsTransformer extends AbstractTransformer {
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {
		$abbreviation = "";
		
		// for this collection, we actually have a lot of data that we want
		// to abbreviate in the header.  so, if our $header isn't in the
		// list we specify here, we'll abbreviate it; it's easier to say what
		// not to abbreviate than to specify what we do!
		
		if (!in_array($header, ["spell", "spell_tags", "spell_category"])) {
			switch ($header) {
				case "damage":
					$abbreviation = "DMG";
					break;
				
				case "duration":
					$abbreviation = "DUR";
					break;
				
				default:
					$abbreviation = $this->abbreviate($header);
					break;
			}
		}
		
		return $abbreviation;
	}
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderClasses(string $header, array $records): string {
		$classes = "";
		
		if ($header === "spell_category") {
			$classes = "w25";
		} elseif ($header !== "spell" && $header !== "spell_tags") {
			
			// if this was the category header, we handled things above.
			// now, if what we're looking at is neither the spells nor the
			// spell tags header, we're looking at one of our thinner data
			// columns.
			
			$classes = "nowrap text-center";
		}
		
		return $classes;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getSearchbarValue(string $column, string $value, array $record): string {
		$sbValue = "";
		
		// our search bar provides the means to search for a spell or filter
		// on categories and tags.  so, we'll need to make sure that our
		// cells can tell the searchbar about their contents.
		
		switch ($column) {
			case "spell":
				$sbValue = strip_tags($value);
				break;
				
			case "spell_tags":
			case "spell_category":
				
				// our $value is the text of our tags and categories.
				// but, we want the numeric ID of those data.  luckily,
				// we can get that information out of our $record.  but,
				// we need to construct the right index.
				
				$index = $column === "spell_category"
					? "spell_category_id"
					: "spell_tags_ids";
				
				$sbValue = $record[$index] ?? "";
				break;
		}
		
		return $sbValue;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {
	
		// the only alteration we need to do here is to our spell's name.  we
		// want to make it a clicker for the display toggling behavior for
		// our descriptive row as follows:
		
		return $column === "spell"
			? sprintf('<a href="#">%s</a>', $value)
			: $value;
			
	}
}
