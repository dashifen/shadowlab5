<?php

namespace Shadowlab\CheatSheets\Other\Qualities;

use Dashifen\Domain\Payload\PayloadInterface;
use Shadowlab\Framework\Domain\AbstractTransformer;

class QualitiesTransformer extends AbstractTransformer {
	public function transformRead(PayloadInterface $payload): PayloadInterface {
		
		// our payload contains either one quality or all of them.  once we
		// determine which is the case here, we can know what to do next.
		
		$original = $payload->getDatum("qualities");
		$transformed = $payload->getDatum("count") === 1
			? $this->transformOne($original)
			: $this->transformAll($original);
		
		$payload->setDatum("qualities", $transformed);
		return $payload;
	}
	
	/**
	 * @param array $quality
	 *
	 * @return array
	 */
	protected function transformOne(array $quality): array {
		
		// at the moment, showing a single quality isn't something we
		// care to do.  so, we'll just return our parameter directly and
		// worry about this later.
		
		return $quality;
	}
	
	/**
	 * @param array $qualities
	 *
	 * @return array
	 */
	protected function transformAll(array $qualities): array {
		
		// the qualities array has one index per quality.  but, the collection
		// view wants a set of table rows -- one for the summary and one for
		// the description -- per quality.  so, our primary purpose here is to
		// transform our $qualities into that structure for display on-screen.
		// we'll also help to prepare some information that relies on our
		// data but is targeted at the searchbar interface.
		
		return [
			"headers" => $this->constructColumnHeaders($qualities),
			"bodies"  => $this->constructTableBodies($qualities),
		];
	}
	
	/**
	 * @param array $qualities
	 *
	 * @return array
	 */
	protected function constructColumnHeaders(array $qualities): array {
		$descriptiveKeys = array_merge(AbstractTransformer::DESCRIPTIVE_KEYS, ["minimum", "maximum"]);
		$headers = $this->extractHeaders($qualities, $descriptiveKeys);
		
		// now that we've gathered our column headers, we want to apply some
		// classes to them for the screen as follows:
		
		foreach ($headers as $i => $header) {
			$classes = $abbreviation = "";
			
			switch ($header) {
				case "cost":
					$classes = "w10 text-right";
					break;
				
				case "freakish":
				case "metagenetic":
					$abbreviation = $this->abbreviate($header);
					$classes = "w5 text-center";
					break;
			}
			
			
			$headers[$i] = [
				"id"           => $this->sanitize($header),
				"display"      => $this->unsanitize($header),
				"abbreviation" => $abbreviation,
				"classes"      => $classes,
			];
		}
		
		return array_values($headers);
	}
	
	/**
	 * @param array $qualities
	 *
	 * @return array
	 */
	protected function constructTableBodies(array $qualities): array {
		$bodies = [];
		
		foreach ($qualities as $quality) {
			
			// here's where we split one quality up into a description and
			// data; the data is what our collection view calls the summary.
			// but, first, we can identify some important details for this
			// row.
			
			$bodies[] = [
				"recordId"    => array_shift($quality),
				"bookId"      => $quality["book_id"],
				"description" => $this->extractDescription($quality),
				"data"        => $this->extractData($quality),
			];
		}
		
		return $bodies;
	}
	
	/**
	 * @param array $quality
	 * @param array $descriptiveKeys
	 *
	 * @return array
	 */
	protected function extractData(array $quality, array $descriptiveKeys = AbstractTransformer::DESCRIPTIVE_KEYS): array {
		$descriptiveKeys = array_merge(AbstractTransformer::DESCRIPTIVE_KEYS, ["minimum", "maximum"]);
		$data = parent::extractData($quality, $descriptiveKeys);
		
		// our data is, for this table, just the quality name and the cost of
		// it.  we'll need to do a little bit of work on both of those, but
		// it's not much.
		
		foreach ($data as $i => &$datum) {
			switch ($datum["column"]) {
				case "quality":
					$datum["searchbarValue"] = strip_tags($datum["html"]);
					$datum["html"] = sprintf('<a href="#">%s</a>', $datum["html"]);
					break;
				
				case "cost":
					
					// for our cost, we want the searchbar value to be either
					// positive or negative.  if the first character of our
					// on-screen display is a "-" character, then we're
					// negative.
					
					$datum["searchbarValue"] = substr($datum["html"], 0, 1) === "-"
						? "negative"
						: "positive";
					
					break;
					
				case "freakish":
				case "metagenetic":
					if ($datum["html"] === "Y") {
						$datum["searchbarValue"] = "1";
					}
					
					break;
			}
		}
		
		return $data;
	}
}
