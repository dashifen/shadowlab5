<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\Transformer\TransformerInterface;

/**
 * Class Transformer
 *
 * @package Shadowlab\Framework\Domain
 */
abstract class AbstractTransformer implements TransformerInterface {
	public const DESCRIPTIVE_KEYS = ["description", "book", "abbreviation", "page"];
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformCreate(PayloadInterface $payload): PayloadInterface {
		return $payload;
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformRead(PayloadInterface $payload): PayloadInterface {
		
		// our $payload will have either one record or a full collection.
		// the count index will tell us which is which.  then, we call one
		// of the abstract methods below to transform our data using a
		// variable method name.  notice that we also send back our
		// original, untransformed data in case it's useful to our Action.
		
		$original = $payload->getDatum("records");
		$transformed = $payload->getDatum("count") > 1
			? $this->transformCollectionForDisplay($original)
			: $this->transformRecordForDisplay($original);
		
		$payload->setData([
			"records"          => $transformed,
			"original-records" => $original,
		]);
		
		return $payload;
	}
	
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformCollectionForDisplay(array $records): array {
		
		// transforming an entire collection for display means taking our
		// records and splitting them into table headers and bodies:
		
		return [
			"headers" => $this->transformTableHeaders($records),
			"bodies"  => $this->transformTableBodies($records),
		];
	}
	
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformTableHeaders(array $records): array {
		
		// the headers we construct here have the following information:
		// an ID and display, and then optional classes and an abbreviation
		// (e.g. like DV for Drain Value).  first, we want to extract the
		// pertinent header information out of our $records.
		
		$transformed = [];
		$headers = $this->extractHeaders($records);
		foreach ($headers as $header) {
			$temp = [
				"id"           => $this->sanitize($header),
				"display"      => $this->unsanitize($header),
				"abbreviation" => $this->getHeaderAbbreviation($header, $records),
				"classes"      => $this->getHeaderClasses($header, $records),
			];
			
			// to avoid sending "extra" data to the client, we'll simply
			// remove empty indices from our $temp array.  then, we can add
			// it to our list of transformed headers.
			
			$transformed[] = array_filter($temp);
		}
		
		return $transformed;
	}
	
	protected function extractHeaders(array $records): array {
		
		// here, we want to filter and remove information from our $data
		// that shouldn't be used to display table headers.  to do that we
		// want to remove some of our data and return the rest.  but, to
		// do so, we need a specific record and not the set of them.  so
		// we grab the first one and hand it over to another method.
		
		$record = $records[0];
		return $this->extractRecordCells($record);
	}
	
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function extractRecordCells(array $record): array {
		
		// the cells of our table, whether they be in the header or the body
		// of it, are made up of the record data less what we consider to be
		// descriptive keys or keys needed in a record's form.  we start that
		// list with the constant above, but let Domains add to that list
		// when necessary.
		
		$removeThese = array_unique(array_merge(...[
			$this->getDescriptiveKeys(),
			$this->getRemovableKeys(),
			self::DESCRIPTIVE_KEYS,
		]));
		
		return array_values(array_filter(array_keys($record), function($index) use ($removeThese) {
			
			// now, we want to keep the data whose index is not in the
			// $removeThese array and when the field doesn't end in either
			// _id or _ids.  the following conditional tells us when that's
			// the case.
			
			return !in_array($index, $removeThese) && !preg_match("/_ids?$/", $index);
		}));
	}
	
	/**
	 * @return array
	 */
	protected function getDescriptiveKeys(): array {
		
		// like when we gte additional removable keys for our headers, here
		// we want to get more keys that describe this specific record.  most
		// of the time, this won't matter, so we'll implement that common
		// case here.
		
		return [];
	}
	
	/**
	 * @return array
	 */
	protected function getRemovableKeys(): array {
		
		// most of the time, we won't have any more descriptive keys to
		// remove from our table.  but, sometimes we will.  so, we'll
		// implement the common case here and let children override it
		// when we need to.
		
		return [];
	}
	
	/**
	 * @param string $unsanitary
	 *
	 * @return string
	 */
	protected function sanitize(string $unsanitary): string {
		
		// we take this essentially from WordPress to produce ID attribute
		// values from some other sort of string during our transformation.
		// usually, these become column and row IDs for use in headers
		// attributes.  we could do this as a Vue filter, but the client-side
		// does enough work.
		
		$sanitary = str_replace("'", "", $unsanitary);
		$sanitary = strtolower(preg_replace("/[\W_]+/", "-", $sanitary));
		
		if (substr($sanitary, -1, 1) == "-") {
			$sanitary = substr($sanitary, 0, strlen($sanitary) - 1);
		}
		
		return $sanitary;
	}
	
	protected function unsanitize(string $sanitary): string {
		
		// this is the opposite of the above sanitize action.  here we
		// identify things that don't look like letters or numbers and
		// convert them to spaces usually for display on-screen.  so,
		// for example, spell-category becomes spell category.
		
		return preg_replace("/[\W-_]+/", " ", $sanitary);
	}
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	abstract protected function getHeaderAbbreviation(string $header, array $records): string;
	
	/**
	 * @param string $header
	 * @param array  $records
	 *
	 * @return string
	 */
	abstract protected function getHeaderClasses(string $header, array $records): string;
	
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformTableBodies(array $records): array {
		
		// transforming our table bodies is more complicated than our
		// headers because we want to take each record and produce a summary
		// and a description.  so, we loop over our $records and extract
		// information from each of them for these data sets.  then, like
		// our table headers, we also send back information about the
		// record's ID and the book in which the record is found.
		
		$transformed = [];
		foreach ($records as $record) {
			
			// we cheat here:  we always select our ID first from the
			// database, so we can use array_shift() to get that information
			// out of $record.
			
			$transformed[] = [
				"recordId"    => array_shift($record),
				"bookId"      => $record["book_id"] ?? 0,
				"description" => $this->extractDescription($record),
				"summary"     => $this->extractSummary($record),
			];
		}
		
		return $transformed;
	}
	
	protected function extractDescription(array $record): array {
		
		// the description of a record is made up of the actual description
		// as well as the book reference for our record.  the keys within
		// $record that describe these data are stored in our DESCRIPTIVE_KEYS
		// constant.  so, we can filter out those data as follows:
		
		$keys = array_merge(self::DESCRIPTIVE_KEYS, $this->getDescriptiveKeys());
		$description = array_filter($record, function($field) use ($keys) {
			return in_array($field, $keys);
		}, ARRAY_FILTER_USE_KEY);
		
		// now, if our description includes both a book and an abbreviation,
		// we're going to combine them into one field using an <abbr> tag.
		// then, we provide the chance for our children to do an additional
		// descriptive transform and return those results.
		
		if (isset($description["abbr"]) && isset($description["book"])) {
			$description = $this->combineBookAndAbbreviation($description);
		}
		
		return $this->transformRecordDescription($description);
	}
	
	/**
	 * @param array $desc
	 *
	 * @return array
	 */
	protected function combineBookAndAbbreviation(array $desc): array {
		
		// we tested for the existence of our abbr and book indices in the
		// prior method.  here we want to combine them into an <abbr> tag
		// and then remove the book entirely.
		
		$abbr = '<abbr title="%s">%s</abbr>';
		$desc["abbr"] = sprintf($abbr, $desc["book"], $desc["abbr"]);
		unset($desc["book"]);
		return $desc;
	}
	
	/**
	 * @param array $description
	 *
	 * @return array
	 */
	protected function transformRecordDescription(array $description): array {
		
		// most of the time, we don't need to do any more work on our
		// descriptions.  but especially for items that are described in
		// multiple fields (e.g. the adept power description and then the
		// list of ways that reduce its cost), this function can be
		// overwritten to do that extra work.
		
		return $description;
	}
	
	protected function extractSummary(array $record): array {
		
		// the summary is made up of the for the columns we identified when
		// extracting headers above.  so, we want to pass our record through
		// the same filter as we did for that method.
		
		$summary = [];
		$columns = $this->extractRecordCells($record);
		$cells = array_filter($record, function($index) use ($columns) {
			return in_array($index, $columns);
		}, ARRAY_FILTER_USE_KEY);
		
		foreach ($cells as $column => $contents) {
			$contents = (string)$contents;
			
			// it stands to reason that individual Domains might have work
			// to do here, especially with respect to information about the
			// searchability of these data.  so, we'll provide functions
			// herein that'll provide children the ability to mess with
			// these data.
			
			$temp = [
				"column"             => $this->sanitize($column),
				"searchbarValue"     => $this->getSearchbarValue($column, $contents, $record),
				"searchbarValueList" => $this->isSearchbarValueList($column, $contents, $record) ? "1" : "0",
				"html"               => $this->getCellContent($column, $contents, $record),
			];
			
			// now, we don't want to send "extra" data to the client, so we're
			// going to remove empty indices in our $temp array before adding
			// it to our summary.
			
			$summary[] = array_filter($temp);
		}
		
		return $summary;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	abstract protected function getSearchbarValue(string $column, string $value, array $record): string;
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return bool
	 */
	protected function isSearchbarValueList(string $column, string $value, array $record): bool {
		
		// for most Domains that need a searchbar value list, those lists
		// are made up of underscore separated lists that are prefixed and
		// suffixed by underscores (e.g. _1_2_3_4_5_).  that's a pattern
		// we can match with a regex.  however, those columns aren't often
		// a part of our table display; i.e. they've been removed.  so, if
		// we have an index in our record that matches this column but with
		// "_ids" added to it, and the value of that index matches our
		// pattern, we'll assume this is a searchbar list.  we'll also test
		// our value parameter, just in case.
		
		$idsCol = $column . "_ids";
		$idsVal = $record[$idsCol] ?? "";
		
		$possibilities = [$idsVal, $value];
		foreach ($possibilities as $possibility) {
			if (preg_match("/_(\d+_)+/", $possibility)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * @param string $column
	 * @param string $value
	 * @param array  $record
	 *
	 * @return string
	 */
	abstract protected function getCellContent(string $column, string $value, array $record): string;
	
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected function transformRecordForDisplay(array $record): array {
		return $record;
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformUpdate(PayloadInterface $payload): PayloadInterface {
		
		// when we transform a payload for updating, what we're really doing
		// is removing information from our data that we don't need to insert.
		// we can identify these data because they'll be optional (i.e.
		// nullable) and empty in our record.
		
		$schema = $payload->getDatum("schema");
		$record = $payload->getDatum("record");
		foreach ($schema as $column => $columnData) {
			if ($columnData["IS_NULLABLE"] === "YES") {
				
				// now that we know our column is nullable, we need to see
				// if the record's value for that column is empty.  we use
				// empty in addition to our null coalescing operator so that
				// values like zero and the empty string won't be read as
				// real values.
				
				$value = $record[$column] ?? "";
				if (empty($value)) {
					
					// if our value is empty, we actually unset it from our
					// record.  that'll allow the database to store the default
					// value (NULL or otherwise) in its place.
					
					unset($record[$column]);
				}
			}
		}
		
		// now, we store our transformed $record back in the payload and
		// send it back to our Domain for processing.  we don't worry about
		// sending back the original record since, if we need it, it's
		// already there.
		
		$payload->setDatum("record", $record);
		return $payload;
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformDelete(PayloadInterface $payload): PayloadInterface {
		return $payload;
	}
	
	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function abbreviate(string $string): string {
		
		// it's common that we want to abbreviate information that we
		// display in table headers using our collection view.  this
		// method uses a regex to identify the first character of
		// $string as well as any letter after an underscore. so,
		// drain_value would be come DV and type would become T.
		
		preg_match_all("/(?:^|_)([a-z])/", $string, $matches);
		return strtoupper(join("", $matches[1]));
	}
	
	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function deduplicateAndSort(array $data): array {
		return $this->deduplicate($data, true);
	}
	
	/**
	 * @param array $data
	 * @param bool  $sort
	 *
	 * @return array
	 */
	protected function deduplicate(array $data, bool $sort = false): array {
		
		// the $data array is assumed to be non-unique.  so, we want to
		// make it so by removing duplicates.  we'll also trim values to
		// avoid "foo " and "foo" being different.
		
		$data = array_filter($data, function($datum) {
			return trim($datum);
		});
		$data = array_unique($data);
		
		// if our sort flag is set, then we'll also perform an associative
		// sort on our $data.
		
		if ($sort) {
			asort($data);
		}
		
		return $data;
	}
}
