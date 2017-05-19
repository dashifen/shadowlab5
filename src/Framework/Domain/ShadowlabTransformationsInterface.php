<?php

namespace Shadowlab\Framework\Domain;

/**
 * Interface ShadowlabTransformations
 *
 * @package Shadowlab\Framework\Domain
 */
interface ShadowlabTransformationsInterface {
	/**
	 * @param string $unsanitary
	 *
	 * @return string
	 *
	 * replaces spaces and punctuation with hyphens
	 */
	public function sanitize(string $unsanitary): string;
	
	/**
	 * @param string $sanitary
	 *
	 * @return string
	 *
	 * undoes the sanitize operation, though not guaranteed to
	 * re-produce the exact original string.
	 */
	public function unsanitize(string $sanitary): string;
	
	/**
	 * @param array $data
	 * @param bool  $sort
	 *
	 * @return array
	 *
	 * removes duplicates from the array and, if the sort flag is true,
	 * alphabetizes it prior to sending it back to the calling scope.
	 */
	public function deduplicate(array $data, bool $sort = false): array;
	
	/**
	 * @param array $data
	 *
	 * @return array
	 *
	 * a convenience method to set the flag on the prior one.
	 */
	public function deduplicateAndSort(array $data): array;
	
	/**
	 * @param string $string
	 *
	 * @return string
	 *
	 * given a string, returns an abbreviation for it.
	 */
	public function abbreviate(string $string): string;
}
