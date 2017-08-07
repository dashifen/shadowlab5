<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\Transformer\TransformerInterface;

/**
 * Class Transformer
 *
 * This is a default Transformer for our app.  It simply returns
 * the exact payload that it receives in all cases.  Children of
 * this class will be able to override only the methods they need
 * to use without cluttering up their definitions with the other
 * stuff that isn't important to them.
 *
 * @package Shadowlab\Framework\Domain
 */
class Transformer implements TransformerInterface, ShadowlabTransformationsInterface {
	public const DESCRIPTIVE_KEYS = ["description", "book", "abbr", "page"];
	
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
		return $payload;
	}
	
	/**
	 * @param PayloadInterface $payload
	 *
	 * @return PayloadInterface
	 */
	public function transformUpdate(PayloadInterface $payload): PayloadInterface {
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
	
	public function abbreviate(string $string): string {
		
		// it's common that we want to abbreviate information that we
		// display in table headers using our collection view.  this
		// method uses a regex to identify the first character of
		// $string as well as any character after an underscore. so,
		// drain_value would be come DV and type would become T.
		
		preg_match_all("/(?:^|_)([a-z])/", $string, $matches);
		return strtoupper(join("", $matches[1]));
	}
	
	public function sanitize(string $unsanitary): string {
		
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
	
	public function unsanitize(string $sanitary): string {
		
		// this is the opposite of the above sanitize action.  here we
		// identify things that don't look like letters or numbers and
		// convert them to spaces usually for display on-screen.  so,
		// for example, spell-category becomes spell category.
		
		return preg_replace("/[\W-_]+/", " ", $sanitary);
	}
	
	public function deduplicate(array $data, bool $sort = false): array {
		
		// the $data array is assumed to be non-unique.  so, we want to
		// make it so by removing duplicates.  we'll also trim values to
		// avoid "foo " and "foo" being different.
		
		$data = array_filter($data, function($datum) { return trim($datum); });
		$data = array_unique($data);
		
		// if our sort flag is set, then we'll also perform an associative
		// sort on our $data.
		
		if ($sort) {
			asort($data);
		}
		
		return $data;
	}
	
	public function deduplicateAndSort(array $data): array {
		return $this->deduplicate($data, true);
	}
	

	protected function extractHeaders(array $data, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// our $data is a set of data selected from the database.  if we're
		// transforming it, then we've already confirmed that there's at least
		// one datum in our array, and we can use it to work with its indices.
		
		$indices = array_keys($data[0]);
		
		// the data we want to filter and remove from our array are any
		// of our descriptive keys in the constant above, the sanitized
		// column usually used for URL matching, and any column ending in
		// "_id" to skip numeric keys in the database.  we'll make our
		// regex here and the use it via closure in the anonymous
		// function below.
		
		$keys = array_merge($descriptiveKeys, ["sanitized", ".+_id$"]);
		
		$regex = "/" . join("|", $keys) . "/";
		return array_filter($indices, function($index) use ($regex) {
			
			// if we did not find a match against our $regex here, then
			// this index should stay in.  to return true, we want to see
			// that our count of matches is exactly zero.
			
			$matches = preg_match($regex, $index);
			return $matches === 0;
		});
	}
	
	protected function extractData(array $spell, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// this method is used by transformers that prepare data for our
		// collection view.  in that view, any key not listed in our constant
		// above is considered data.  using array_filter, we can return true
		// for all fields not in that constant as follows which'll remove
		// the descriptive data from our $record.
		
		$filtered = array_filter($spell, function($field) use ($descriptiveKeys) {
			return !in_array($field, $descriptiveKeys);
		}, ARRAY_FILTER_USE_KEY);
		
		// now that we've removed our descriptive keys from our data, we need
		// to arrange what's left so that our Vue can access both the field
		// and value information rather than just the value.  plus, we'll also
		// remove anything that ends in _id since those shouldn't be in our
		// data either.
		
		$temp = [];
		foreach ($filtered as $field => $value) {
			if (preg_match("/_id$/", $field)) {
				continue;
			}
			
			$temp[] = [
				"column" => $this->sanitize($field),
				"html"   => $value,
			];
		}
		
		return $temp;
	}
	
	protected function extractDescription(array $record, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// this is the opposite, effectively of the prior method.  the logic
		// is the same, but this time we want to filter out the data.  so we
		// want to return true from our filter callback when our fields are
		// in the constant above so that we're left with only them.
		
		$desc = array_filter($record, function($field) use ($descriptiveKeys) {
			return in_array($field, $descriptiveKeys);
		}, ARRAY_FILTER_USE_KEY);
		
		// now, we do one other thing:  if our description includes both a
		// book and an abbreviation, we're going to combine them into one
		// field using an <abbr> tag.
		
		if (isset($desc["abbr"]) && isset($desc["book"])) {
			$desc = $this->combineBookAndAbbreviation($desc);
		}
		
		return $desc;
	}
	
	protected function combineBookAndAbbreviation(array $desc): array {
		
		// we tested for the existence of our abbr and book indices in the
		// prior method.  here we want to combine them into an <abbr> tag
		// and then remove the book entirely.
		
		$abbr = '<abbr title="%s">%s</abbr>';
		$desc["abbr"] = sprintf($abbr, $desc["book"], $desc["abbr"]);
		unset($desc["book"]);
		return $desc;
	}
}
