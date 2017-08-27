<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Shadowlab\Framework\Domain\AbstractTransformer;

class MatrixActionsTransformer extends AbstractTransformer {
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {

		// for this one, oddly, there's no need for abbreviations.  all of
		// the header columns are good just the way they are.

		return "";
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
			case "marks":
			case "actions":
				$classes = "w5 text-center";
				break;
				
			case "matrix_action":
				$classes = "w20";
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
			case "matrix_action":
				$sbValue = strip_tags($column);
				break;
				
			case "marks":
			case "action":
				$sbValue = $value;
				break;
		}
		
		
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {
		// TODO: Implement getCellContent() method.
	}
	
}
