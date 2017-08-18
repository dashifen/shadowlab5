<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Shadowlab\Framework\Domain\AbstractTransformer;

class QualitiesTransformer extends AbstractTransformer {
	/**
	 * @return array
	 */
	protected function getRemovableKeys(): array {
		
		// the minimum and maximum values are included in our records for
		// use in the form.  but, when we're displaying our information in
		// a collection, we can remove them.
		
		return ["minimum", "maximum"];
	}
	
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {
		
		// most of our columns are fine, but the ones for the metagenetic and
		// freakish flags can be abbreviated as follows:
		
		return $header === "metagenetic" || $header === "freakish"
			? $this->abbreviate($header)
			: "";
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
				$classes = "w10 text-right";
				break;
			
			case "freakish":
			case "metagenetic":
				$classes = "w5 text-center";
				break;
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
		
		switch ($column) {
			case "quality":
				$sbValue = strip_tags($value);
				break;
				
			case "cost":
				
				// if our cost begins with a negative sign, then we want to
				// use a searchbar value of negative.  otherwise, positive.
				
				$sbValue = substr($value, 0, 1) === "-" ? "negative" : "positive";
				break;
				
			case "freakish":
			case "metagenetic":
				if ($value === "Y") {
					$sbValue = "1";
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
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {
		
		// as is often the case, the only change we want to make here is to
		// the quality's name.  that has to become a clicker to control the
		// display toggling behavior of our descriptive row.
		
		return $column === "quality"
			? sprintf('<a href="#">%s</a>', $value)
			: $value;
	}
}
