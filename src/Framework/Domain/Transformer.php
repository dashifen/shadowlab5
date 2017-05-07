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
class Transformer implements TransformerInterface {
	public const DESCRIPTIVE_KEYS = ["description", "abbr", "page"];
	
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
	
	protected function sanitizeId(string $unsanitary): string {
		
		// we take this essentially from WordPress to produce ID attribute
		// values from some other sort of string during our transformation.
		// usually, these become column and row IDs for use in headers
		// attributes.  we could do this as a Vue filter, but the client-side
		// does enough work.
		
		$sanitary = strtolower(preg_replace("/\W+/", "-", $unsanitary));
		
		if (substr($sanitary, -1, 1) == "-") {
			$sanitary = substr($sanitary, 0, strlen($sanitary) - 1);
		}
		
		return $sanitary;
	}
	
	protected function extractData(array $record, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		
		// this method is used by transformers that prepare data for our
		// collection view.  in that view, any key not listed in our constant
		// above is considered data.  using array_filter, we can return true
		// for all fields not in that constant as follows which'll remove
		// the descriptive data from our $record.
		
		$filtered = array_filter($record, function($field) use ($descriptiveKeys) {
			return !in_array($field, $descriptiveKeys);
		}, ARRAY_FILTER_USE_KEY);
		
		// now that we've removed our descriptive keys from our data, we need
		// to arrange what's left so that our Vue can access both the field
		// and value information rather than just the value.
		
		$temp = [];
		foreach ($filtered as $field => $value) {
			$temp[] = [
				"column" => $this->sanitizeId($field),
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
		
		return array_filter($record, function($field) use ($descriptiveKeys) {
			return in_array($field, $descriptiveKeys);
		}, ARRAY_FILTER_USE_KEY);
	}
}
