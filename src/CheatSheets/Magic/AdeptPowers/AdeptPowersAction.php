<?php

namespace Shadowlab\CheatSheets\Magic\AdeptPowers;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;
use Dashifen\Searchbar\SearchbarException;
use Aura\Di\Exception\ServiceNotFound;

class AdeptPowersAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 * @throws SearchbarException
	 * @throws ServiceNotFound
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		$powers = $payload->getDatum("original-records");
		$costs = $this->getPowerCostOptions($powers);
		$ways = $this->getAdeptWayOptions($powers);

		/** @var SearchbarInterface $searchbar */
		
		$searchbar = $this->container->get("Searchbar");
		$searchbar->addSearch("Powers", "power");
		$searchbar->addFilter("Costs", "cost", $costs, "", "All costs");
		
		// since our ways don't appear in our table bodies, we crammed
		// the information about them into the maximum-levels column.
		// that means we need to reference maximum-levels when we want
		// to filter on our Adept Ways.  unfortunate, but it'll work
		// until we design a better way.
		
		$searchbar->addFilter("Ways", "maximum-levels", $ways, "", "All ways");
		$searchbar->addFilter("Books", "book", $this->getBookOptions($payload), "", "All books");
		$searchbar->addReset();
		return $searchbar;
	}
	
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function getPowerCostOptions(array $powers): array {
		$options = [];
		foreach ($powers as $power) {
			$options[$power["cost"]] = $power["cost"];
		}
		
		// we know our array is unique already, but it's likely not
		// in order.  so, we'll sort, and then we'll return it.
		
		asort($options);
		return $options;
	}
	
	/**
	 * @param array $powers
	 *
	 * @return array
	 */
	protected function getAdeptWayOptions(array $powers): array {
		$ways = [];
		foreach ($powers as $power) {
			if (!empty($power["adept_power_ways_ids"])) {
				
				// now that we know we have them, we have to break up
				// the way IDs and the list of ways and then combine them
				// together.  the IDs are separated by underscores and
				// prefixed and suffixed with underscores as well, so we
				// will need to remove blanks.  the names are just comma
				// separated.
				
				$ids = array_filter(explode("_", $power["adept_power_ways_ids"]));
				$names = explode(", ", $power["adept_power_ways"]);
				$ways += array_combine($ids, $names);
			}
		}
		
		// and, like the getOptions() method above, we have a unique array,
		// but not a sorted one.  so, we sort and return.
		
		asort($ways);
		return $ways;
	}
	
	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "adept power";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "adept powers";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "adept_powers";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "adept_power_id";
	}
	
}
