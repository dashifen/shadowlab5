<?php

namespace Shadowlab\Framework\AddOns\Searchbar;

use Dashifen\Searchbar\AbstractSearchbar;
use Dashifen\Searchbar\SearchbarException;

class Searchbar extends AbstractSearchbar implements SearchbarInterface {
	public function parse(array $searchbarData): string {
		
		//echo "<pre>" . print_r($searchbarData, true) . "</pre>";
		
		foreach ($searchbarData as $i => $rows) {
			if ($i !== 0) {
				$this->addRow();
			}
			
			foreach ($rows as $element) {
				switch($element["type"]) {
					case "search":
						$this->addSearch($element["label"], $element["for"]);
						break;
						
					case "filter":
						$this->addFilter($element["label"], $element["for"], $element["searchbarValues"], "", $element["defaultText"]);
						break;
					
					case "toggle":
						$this->addToggle($element["label"], $element["for"], "");
						break;
				}
			}
			
			if ($i === 0) {
				$this->addReset();
			}
		}
		
		return $this->getBar();
	}
	
	public function addReset(string $label = '<i class="fa fa-fw fa-undo" aria-hidden="true" title="Reset"></i>') {
		parent::addReset($label);
	}
	
	protected function getElementType(array $classes): string {
		
		// within $classes should be one of three keywords if they're to
		// be applied to data that we can search/filter with this bar.  the
		// keywords are search, toggle, and filter.  if we don't find any
		// of them or if we find more than one of them, we'll throw an
		// exception to handle elsewhere.
		
		$intersection = array_intersect($classes, ["search","toggle","filter"]);
		$count = sizeof($intersection);
		
		if ($count === 1) {
			return array_shift($intersection);
		}
		
		// if we're still here, we'll throw an exception.  notice that we
		// pass along the size of our intersection as the code; this might
		// be useful one day...
		
		throw new SearchbarException("Element type parse error", $count);
	}
}
