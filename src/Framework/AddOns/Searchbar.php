<?php

namespace Shadowlab\Framework\AddOns;

use Dashifen\Searchbar\AbstractSearchbar;
use Dashifen\Searchbar\SearchbarException;

class Searchbar extends AbstractSearchbar {
	public function parse(array $data): string {
		
		// echo "<pre>" . print_r($data, true) . "</pre>";
		
		// we expect a headers index within our $data which contains
		// information about our searchbar.  but, if we don't have one,
		// we'll just skip everything herein and end up with an empty
		// bar
		
		$headers = $data["headers"] ?? [];
		foreach ($headers as $header) {
			$classes = explode(" ", $header["classes"] ?? "");
			
			if (in_array("addRow", $classes)) {
				
				if ($this->index === 0) {
					$this->addReset();
				}
				
				$this->addRow();
			}
			
			try {
				$type = $this->getElementType($classes);
				
				switch ($type) {
					case "search":
						$this->addSearch($header["display"], $header["id"]);
						break;
					
					case "toggle":
						$this->addToggle($header["display"], $header["id"]);
						break;
					
					case "filter":
						$defaultText = $header["defaultText"] ?? "";
						$this->addFilter($header["display"], $header["id"], $header["searchbarValues"], "", $defaultText);
						break;
				}
			} catch (SearchbarException $e) {
				if ($e->getCode() !== 0) {
					throw $e;
				}
			}
		}
		
		if ($this->index === 0) {
			$this->addReset();
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
