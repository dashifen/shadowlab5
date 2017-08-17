<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Shadowlab\Framework\Domain\AbstractTransformer;

class AdeptPowersTransformer extends AbstractTransformer {
	protected const HTML = "<h3>Ways</h3><p>This adept power receives a discount from the following Adept Ways:</p><ul><li>%s</li></ul>";
	protected const REMOVABLE_KEYS = ["adept_power_ways", "adept_power_way_ids", "levels"];
	
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function transformAll(array $powers): array {
		
		// when we receive our adept powers, each index in $powers is a
		// complete representation of our powers.  but, what we really
		// want is the table-ready representation for our collection view.
		// so here we separate our $powers into the table headers and
		// bodies we need there.
		
		$transformed = [
			"headers" => $this->constructHeaders($powers),
			"bodies"  => $this->constructBodies($powers),
		];
		
		return $transformed;
	}
	
	/**
	 * @param $powers array
	 *
	 * @return array
	 */
	protected function constructHeaders(array $powers): array {
		$headers = [];
		$columns = $this->extractHeaders($powers);
		foreach ($columns as $column) {
			list($classes, $abbreviation) = $this->enhanceHeaders($column);
			
			// the collection view expects ID and display information, but
			// we can also pass it an abbreviation for that display and
			// classes for the HTML element.  we construct an array of those
			// data here and add it to our list of $headers.
			
			$headers[] = [
				"id"           => $this->sanitize($column),
				"display"      => $this->unsanitize($column),
				"abbreviation" => $abbreviation,
				"classes"      => $classes,
			];
		}
		
		return $headers;
	}
	
	/**
	 * @param string $column
	 *
	 * @return array
	 */
	protected function enhanceHeaders(string $column): array {
		$classes = $abbreviation = "";
		
		// here we want to loop over our our columns and for a number
		// of them, specify classes that the collection view uses to
		// help organize our screen.
		
		switch ($column) {
			case "cost":
				$classes = "w5 text-right";
				break;
			
			case "cost_per_level":
				$abbreviation = "C/L";
				$classes = "w5 text-right";
				break;
			
			case "maximum_levels":
				$abbreviation = "LVLs";
				$classes = "w10 text-center";
				break;
				
			case "action":
				$classes = "w10";
				break;
		}
		
		return [$classes, $abbreviation];
	}
	
	/**
	 * @param array $data
	 * @param array $descriptiveKeys
	 *
	 * @return array
	 */
	protected function extractHeaders(array $data, array $descriptiveKeys = AbstractTransformer::DESCRIPTIVE_KEYS): array {
		
		// the textual information about our adept ways would usually be left
		// alone by our parent's method.  so, we'd better pass a slightly
		// different list of keys to filter out of our results here to avoid
		// these data becoming a column in our table.
		
		$keys = array_merge($descriptiveKeys, self::REMOVABLE_KEYS);
		return parent::extractHeaders($data, $keys);
	}
	
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function constructBodies(array $powers): array {
		$bodies = [];
		foreach ($powers as $power) {
			
			// here's where we split one $power into two rows, a summary
			// and a description, for our collection view.  luckily, much
			// of the work happens in other methods.  we've carefully
			// arranged the information about our powers so that the adept
			// power ID is the first item in the array.  we can shift it
			// off first, and then the remaining data gets passed to our
			// extraction methods.
			
			$power_id = array_shift($power);
			
			// unlike some of our other records, an adept power's description
			// is made up of both the actual description and then some
			// information about the Adept Ways that reduce it's cost.  we
			// want to merge them together using this first method.
			
			$power = $this->mergeDescriptions($power);
			$description = $this->extractDescription($power);
			$data = $this->extractSummary($power);
			
			foreach ($data as &$datum) {
				$datum = $this->enhanceDatum($datum, $power["adept_power_way_ids"]);
			}
			
			$bodies[] = [
				"recordId"    => $power_id,
				"bookId"      => $power["book_id"],
				"description" => $description,
				"data"        => $data,
			];
		}
		
		return $bodies;
	}
	
	/**
	 * @param array $power
	 *
	 * @return array
	 */
	protected function mergeDescriptions(array $power): array {
		
		// if there are ways for this power, then we want to take the comma
		// separated list of them that the database sends us and convert it
		// using the HTML constant specified above.
		
		if (!empty($ways = $power["adept_power_ways"])) {
			$ways = sprintf(self::HTML, str_replace(", ", "</li><li>", $ways));
			
			// now, we take the HTML be built for our information about
			// adept ways and add it to our description.  and, we unset
			// the original list of ways because we don't want it to be
			// a part of our table body.
			
			unset($power["adept_power_ways"]);
			$power["description"] .= $ways;
		}
		
		return $power;
	}
	
	/**
	 * @param array $power
	 * @param array $descriptiveKeys
	 *
	 * @return array
	 */
	protected function extractSummary(array $power, array $descriptiveKeys = AbstractTransformer::DESCRIPTIVE_KEYS): array {
		
		// like our header method above, we want to make sure that the
		// information about our adept ways is not a part of our table
		// bodies.  we already removed the textual data in our merge
		// method above, here we make sure that their IDs don't end up
		// in our table body.
		
		$keys = array_merge($descriptiveKeys, self::REMOVABLE_KEYS);
		return parent::extractSummary($power, $keys);
	}
	
	/**
	 * @param array  $datum
	 * @param string $adept_power_way_ids
	 *
	 * @return array
	 */
	protected function enhanceDatum(array $datum, string $adept_power_way_ids = null): array {
		
		// like the method above which enhances our headers, this one
		// determines additional information about our data that the view
		// requires to display our collection.
		
		switch($datum["column"]) {
			case "adept-power":
				$datum["searchbarValue"] = strip_tags($datum["html"]);
				
				// the database doesn't add our <a> tag to the adept_power
				// display.  we'll do so here to trigger the display of its
				// description.
				
				$datum["html"] = sprintf('<a href="#">%s</a>', $datum["html"]);
				break;
				
			case "cost":
				$datum["searchbarValue"] = $datum["html"];
				break;
				
			case "maximum-levels":
				
				// we've been very careful to remove information about
				// our ways from our columns because we merged it into
				// our descriptions.  but, this means that we no longer
				// have the means by which to filter based on them.
				// since the maximum_levels column doesn't appear in
				// our searchbar, we can use it to inject these data
				// into the DOM.
				
				$datum["searchbarValue"] = $adept_power_way_ids;
				$datum["searchbarValueList"] = 1;
				break;
				
			case "action":
				$datum["searchbarValue"] = $datum["html"];
				$datum["html"] = ucfirst($datum["html"]);
				break;
		}
		
		return $datum;
	}
	
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function transformOne(array $powers): array {
		return $powers;
	}
	
}
