<?php

namespace Shadowlab\CheatSheets\Matrix\Programs;

use Shadowlab\Framework\Domain\AbstractTransformer;

class ProgramsTransformer extends AbstractTransformer {
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderAbbreviation(string $header, array $records): string {
		switch ($header) {
			case "max_rating":
				return '<abbr title="Max Rating">Rating</abbr>';

			case "availability":
				return '<abbr title="Availability">Avail</abbr>';
		}

		return $header;
	}

	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderClasses(string $header, array $records): string {
		if ($header === "program" || $header === "program_type") {
			return "nowrap";
		}

		return "text-right w5";
	}

	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getSearchbarValue(string $column, string $value, array $record): string {
		switch ($column) {
			case "program":
				return strip_tags($value);
		}

		return $value;
	}

	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getCellContent(string $column, string $value, array $record): string {

		// the only alteration we need to do here is to our programs's name.
		// we want to make it a clicker for the display toggling behavior for
		// our descriptive row as follows:

		return $column === "program"
			? sprintf('<a href="#">%s</a>', $value)
			: $value;
	}
}