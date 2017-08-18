<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Shadowlab\Framework\Domain\AbstractTransformer;

class AdeptPowersTransformer extends AbstractTransformer {
	protected const HTML = "<h3>Ways</h3><p>This adept power receives a discount from the following Adept Ways:</p><ul><li>%s</li></ul>";
	
	/**
	 * @return array
	 */
	protected function getRemovableKeys(): array {
		
		// we know information about our IDs will automatically be removed,
		// as will the data about our description.  but, we have additional
		// data in our records that shouldn't become table columns.  those
		// data are as follows:
		
		return ["adept_power_ways", "levels"];
	}
	
	/**
	 * @return array
	 */
	protected function getDescriptiveKeys(): array {
		
		// in addition to the book, page, etc. data about our description,
		// we want to consider the adept_power_ways descriptive as well.
		
		return ["adept_power_ways"];
	}
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {
		$abbreviation = "";
		
		switch ($header) {
			case "cost_per_level":
				$abbreviation = "C/L";
				break;
				
			case "maximum_levels":
				$abbreviation = "LVLs";
				break;
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
		
		switch ($header) {
			case "cost":
			case "cost_per_level":
				$classes = "w5 text-right";
				break;
				
			case "maximum_levels":
				$classes = "w10 text-center";
				break;
				
			case "action":
				$classes = "w10";
				break;
		}
		
		return $classes;
	}
	
	/**
	 * @param array $description
	 *
	 * @return array
	 */
	protected function transformRecordDescription(array $description): array {
		
		// for adept powers, the $description array will contain both a
		// description and an adept_power_ways index.  we want to take the
		// latter and append it to the former after making it into an
		// HTML section.
		
		$ways = $description["adept_power_ways"] ?? "";
		
		if (!empty($ways) > 0) {
			
			// now that we know this power gets discounted by at least one
			// way, we can convert it from a comma separated list to an
			// actual HTML list using the constant above.  then we add it
			// to the general description, and remove the now superfluous
			// index.
			
			$htmlWays = sprintf(self::HTML, str_replace(", ", "</li><li>", $ways));
			$description["description"] .= $htmlWays;
			unset($description["adept_power_ways"]);
		}
		
		return $description;
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
		
		switch ($column) {
			case "adept_power":
				$sbValue = strip_tags($value);
				break;
				
			case "cost":
			case "action":
				$sbValue = $value;
				break;
				
			case "maximum_levels":
				
				// we've been very careful to remove information about
				// our ways from our columns because we merged it into
				// our descriptions.  but, this means that we no longer
				// have the means by which to filter based on them.
				// since the maximum_levels column doesn't appear in
				// our searchbar, we can use it to inject these data
				// into the DOM.
				
				if (!empty($record["adept_power_ways_ids"] ?? "")) {
					$sbValue = $record["adept_power_ways_ids"];
				}
				
				break;
		}
		
		return $sbValue;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return bool
	 */
	protected function isSearchbarValueList(string $column, string $value, array $record): bool {
		
		// just like the prior method, we can't rely on SOP for our
		// searchbar values list for the adept ways because we've removed
		// those data from our table.  therefore, we'll hijack the maximum
		// levels of a power to get them into the DOM.  so, if this is that
		// column and we have way data, then we want to return true.

		return $column === "maximum_levels" && !empty($record["adept_power_ways_ids"] ??"");
	}
	
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {
		switch ($column) {
			case "adept_power":
				$value = sprintf('<a href="#">%s</a>', $value);
				break;
				
			case "action":
				$value = ucfirst($value);
				break;
		}
		
		return $value;
	}
}
