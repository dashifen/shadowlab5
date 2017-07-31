<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\Transformer\TransformerInterface;
use Shadowlab\Framework\Domain\Transformer;

class SpellsTransformer extends Transformer {
	public function transformRead(PayloadInterface $payload): PayloadInterface {
		
		// our $payload will have either one or many books.  the count
		// index will tell us which is which.  then, we call one of the
		// methods below to transform our data.
		
		$original = $payload->getDatum("spells");
		$transformed = $payload->getDatum("count") > 1
			? $this->transformAll($original)
			: $this->transformOne($original);
		
		$payload->setDatum("spells", $transformed);
		return $payload;
	}
	
	protected function transformAll(array $spells): array {
		
		// our full list of spells is displayed using our collection view.
		// so, we need to transform our array of spells into the structure
		// that it requires to produce our display.  first, we'll construct
		// our headers:
		
		$headers = $this->constructHeaders($spells);
		$bodies = $this->constructBodies($spells);
		
		return [
			"headers"   => $headers,
			"bodies"    => $bodies,
		];
	}
	
	protected function constructHeaders(array $spells): array {
		$headers = [];
		
		// the headers for our view are the spell itself, its category, and
		// then a bunch of icons for range, duration, etc.  we'll up an array
		// for all that now.
		
		$columns = $this->extractHeaders($spells);
		
		foreach ($columns as $column) {
			$abbreviation = "";
			$classes = "";
			
			switch ($column) {
				case "spell":
				case "spell_tags":
					
					// these two require nothing, but we don't want them
					// to get caught in the default case below.
					
					break;
				
				case "spell_category":
					$classes = "w25";
					break;
				
				default:
					$classes = "nowrap text-center";
					$abbreviation = $column !== "damage"
						? $this->abbreviate($column)
						: "DMG";
					
					break;
			}
			
			$headers[] = [
				"id"           => $this->sanitize($column),
				"display"      => $this->unsanitize($column),
				"abbreviation" => $abbreviation,
				"classes"      => $classes,
			];
		}
		
		return $headers;
	}
	
	protected function extractHeaders(array $data, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		$headers = parent::extractHeaders($data);
		
		foreach ($headers as $i => $header) {
			
			// there's one more that we want to remove before we
			// continue here:  the spell_tags_ids.
			
			if ($header === "spell_tags_ids") {
				unset($headers[$i]);
				break;
			}
		}
		
		return $headers;
	}
	
	protected function constructBodies(array $spells): array {
		$bodies = [];
		
		foreach ($spells as $spell) {
			$rows = [];
			
			// each spell is currently a single record; we want to
			// split it into two rows within a single <tbody> element
			// on-screen.  the first row is for our data, the second
			// for our description.  we select from the database so
			// that our first array index is the ID for our <tbody>.
			// then, we want to add our book ID as a part of the row,
			// too.
			
			$rows["tbodyId"] = array_shift($spell);
			$rows["bookId"] = $spell["book_id"];
			
			// now, the parent class can help separate data from
			// description:
			
			$rows["description"] = $this->extractDescription($spell);
			$rows["data"] = $this->extractData($spell);
			$bodies[] = $rows;
		}
		
		return $bodies;
	}
	
	protected function extractData(array $spell, array $descriptiveKeys = Transformer::DESCRIPTIVE_KEYS): array {
		$descriptiveKeys = array_merge($descriptiveKeys, ["spell_tags_ids"]);
		$data = parent::extractData($spell, $descriptiveKeys);
		
		// there's additional modifications for this specific record set
		// that our parent can't know to do.  these relate to the setup
		// of our searchbar values.
		
		foreach ($data as $i => &$datum) {
			switch ($datum["column"]) {
				case "spell":
					$datum["searchbarValue"] = strip_tags($datum["html"]);
					break;
				
				case "spell-category":
					$datum["searchbarValue"] = $spell["spell_category_id"];
					break;
					
				case "spell-tags":
					$datum["searchbarValue"] = $spell["spell_tags_ids"];
					$datum["searchbarValueList"] = 1;
					break;
			}
		}
		
		return $data;
	}
	
	protected function transformOne(array $spells): array {
		return $spells;
	}
}
