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
		$searchbar = $this->constructSearchbar($spells);
		
		return [
			"headers"   => $headers,
			"bodies"    => $bodies,
			"searchbar" => $searchbar,
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
	
	protected function extractHeaders(array $data): array {
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
	
	protected function constructSearchbar(array $spells): array {
		
		// while the searchbar doesn't transform the data itself, it uses
		// that data to create a bar that we use on screen to search within
		// it.  so, since our transformers are the object which manipulate
		// the data between the domain and the action, it make sense to
		// keep this manipulation here.
		
		$tags = [];
		$books = [];
		$categories = [];
		foreach ($spells as $i => $spell) {
			$books[$spell["book_id"]] = $spell["abbr"];
			$categories[$spell["spell_category_id"]] = $spell["spell_category"];
			
			// those two were easy, but our tags are harder because it's a
			// one-to-many relationship between spells and tags.  so, we need
			// to explode our tag IDs and our tags and them combine them
			// a follows:
			
			$x = array_filter(explode("_", $spell["spell_tags_ids"]));
			$y = array_filter(explode(", ", $spell["spell_tags"]));
			
			// and, now that we have a $x and $y representing tag IDs and
			// the tags themselves, we can combine those and append the
			// result into $tags.
			
			$tags += array_combine($x, $y);
		}
		
		$searchbar = [
			[
				[
					"type"  => "search",
					"label" => "Spells",
					"for"   => "spell",
				], [
					"type"            => "filter",
					"label"           => "Spell Categories",
					"for"             => "spell-category",
					"defaultText"     => "All Spell Categories",
					"searchbarValues" => $this->deduplicateAndSort($categories),
				], [
					"type"            => "filter",
					"label"           => "Spell Tags",
					"for"             => "spell-tags",
					"defaultText"     => "All Spell Tags",
					"searchbarValues" => $this->deduplicateAndSort($tags),
				], [
					"type"            => "filter",
					"label"           => "Books",
					"for"             => "book",
					"defaultText"     => "All Books",
					"searchbarValues" => $this->deduplicateAndSort($books),
				],
			]
		];
		
		return $searchbar;
	}
	
	protected function transformOne(array $spells): array {
		return $spells;
	}
}
