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
		// TODO: Implement getHeaderAbbreviation() method.
	}

	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	protected function getHeaderClasses(string $header, array $records): string {
		// TODO: Implement getHeaderClasses() method.
	}

	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	protected function getSearchbarValue(string $column, string $value, array $record): string {
		// TODO: Implement getSearchbarValue() method.
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