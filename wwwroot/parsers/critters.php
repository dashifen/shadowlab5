<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Database\Mysql\MysqlInterface;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;
use Shadowlab\Parser\ParserException;

class CrittersParser extends AbstractParser {
	protected const ATTRIBUTE_MAP = [
		"bodmin" => "body",
		"agimin" => "agility",
		"reamin" => "reaction",
		"strmin" => "strength",
		"chamin" => "charisma",
		"intmin" => "intuition",
		"logmin" => "logic",
		"wilmin" => "willpower",
		"inimin" => "initiative",
		"edgmin" => "edge",
		"magmin" => "magic",
		"resmin" => "resonance",
		"depmin" => "depth",
		"essmin" => "essence",
	];

	/**
	 * @var array
	 */
	protected $types = [];

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var array
	 */
	protected $powers = [];

	/**
	 * @var array
	 */
	protected $skills = [];

	/**
	 * @var array
	 */
	protected $programs = [];

	/**
	 * @var array
	 */
	protected $qualities = [];

	/**
	 * CrittersParser constructor.
	 *
	 * @param string         $dataFile
	 * @param MysqlInterface $db
	 *
	 * @throws DatabaseException
	 * @throws ParserException
	 */
	public function __construct(string $dataFile = "", MysqlInterface $db) {
		parent::__construct($dataFile, $db);

		// before we parse individual critters, we need to update the list
		// of critter types and make sure we gather the necessary data to
		// understand what we're going to be looking at.

		$this->updateIdTable("categories", "critter_type", "critters_types");
		$this->types = $this->db->getMap("SELECT critter_type, critter_type_id FROM critters_types");
		$this->attributes = $this->db->getMap("SELECT attribute, attribute_id FROM attributes");
		$this->qualities = $this->db->getMap("SELECT quality, quality_id FROM qualities");
		$this->programs = $this->db->getMap("SELECT program, program_id FROM programs");
		$this->powers = $this->db->getMap("SELECT critter_power, critter_power_id FROM critter_powers");
		$this->skills = $this->db->getMap("SELECT skill, skill_id FROM skills");
	}

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		foreach ($this->xml->metatypes->metatype as $critter) {
			if ($this->isSR5($critter)) {
				$critterId = $this->upsertCritter($critter);
				$this->handleCritterAttributes($critter, $critterId);
				$this->handleOptionalProperties($critter, $critterId);
			}
		}
	}

	/**
	 * @param SimpleXMLElement $critter
	 *
	 * @return bool
	 */
	protected function isSR5(SimpleXMLElement $critter): bool {

		// the critters.xml file includes critters from SR4
		// as well as SR5.  we're only listing SR5 stats at
		// this time, so we'll skip any of them that cannot
		// be found in the $books map.

		$bookId = $this->bookMap[(string) $critter->source] ?? "";
		return is_numeric($bookId);
	}

	/**
	 * @param SimpleXmlElement $critter
	 *
	 * @return int
	 * @throws MysqlException
	 * @throws DatabaseException
	 */
	protected function upsertCritter(SimpleXmlElement $critter): int {
		$critterData = [
			"movement"        => $this->getMovement($critter),
			"critter_type_id" => $this->types[(string) $critter->category],
			"book_id"         => $this->bookMap[(string) $critter->source],
			"page"            => (int) $critter->page,
		];

		$insertData = array_merge($critterData, [
			"guid"    => ($guid = strtolower((string) $critter->id)),
			"critter" => (string) $critter->name,
		]);

		$this->db->upsert("critters", $insertData, $critterData);
		$statement = "SELECT critter_id FROM critters WHERE guid = :guid";
		return $this->db->getVar($statement, ["guid" => $guid]);
	}

	/**
	 * @param SimpleXMLElement $critter
	 *
	 * @return string
	 */
	protected function getMovement(SimpleXMLElement $critter): string {
		if (isset($critter->movement)) {
			$temp = (string) $critter->movement;
			return $temp === "Special" ? $temp : "x2/x4/+2";
		}

		// if we're still here, then we want to decipher the walk,
		// run, and sprint information in our $critter to determine
		// our movement string.  if these three properties don't
		// exist, then we'll return the default above.

		switch ((string) $critter->name) {
			case "Spirit of Air":
				return "x2/x4/+10";

			case "Spirit of Fire":
				return "x2/x4/+5";

			default:
				$walk = explode("/", (string) ($critter->walk ?? ""));
				$run = explode("/", (string) ($critter->run ?? ""));
				$sprint = explode("/", (string) ($critter->sprint ?? ""));

				$format = "x%d/x%d/+%d";
				$ground = [$walk[0], $run[0], $sprint[0]];
				$water = [$walk[1], $run[1], $sprint[1]];
				$air = [$walk[2], $run[2], $sprint[2]];

				$movement = vsprintf($format, $ground);

				if ($water != [0, 0, 0] && $water != [1, 0, 1] && $water != [2, 4, 2]) {
					$movement .= " (" . vsprintf($format, $water) . " swimming)";
				}

				if ($air != [0, 0, 0] && $air != [2, 4, 2]) {
					$movement .= " (" . vsprintf($format, $air) . " flying)";
				}

				return $movement;
		}
	}

	/**
	 * @param SimpleXMLElement $critter
	 * @param int              $critterId
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function handleCritterAttributes(SimpleXMLElement $critter, int $critterId): void {

		// to make things easy, we'll just delete the old attributes
		// and then add the new ones in.  we'll build a list of
		// insertions so that we can do both of these operations in
		// two queries.

		$insertions = [];

		foreach (self::ATTRIBUTE_MAP as $property => $attribute) {
			if (isset($critter->{$property})) {
				$insertions[] = [
					"critter_id"   => $critterId,
					"attribute_id" => $this->attributes[$attribute],
					"rating"       => (string) $critter->{$property},
				];
			}
		}

		$this->db->delete("critters_attributes", ["critter_id" => $critterId]);
		$this->db->insert("critters_attributes", $insertions);
	}

	/**
	 * @param SimpleXMLElement $critter
	 * @param int              $critterId
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function handleOptionalProperties(SimpleXMLElement $critter, int $critterId): void {

		// the following properties are all optional:  some critters
		// have more than others, but all of them have at least one
		// of the following.  since we don't know which has what,  we
		// loop over the properties and check each of them for every
		// critter.  we link the name of the thing in the XML document
		// to the information we need in the loop below.

		$properties = [
			"skills"         => ["skill_id", "critters_skills"],
			"powers"         => ["critter_power_id", "critters_critter_powers"],
			"optionalpowers" => ["critter_power_id", "critters_critter_powers"],
			"complexforms"   => ["program_id", "critters_programs"],
		];

		foreach ($properties as $property => list($propertyId, $table)) {
			if ($this->hasProperty($critter, $property)) {

				// if we're in this block, then we've confirmed that this
				// critter has at least one record withing the specified
				// property (e.g. they have at least one power or skill).
				// so, we gather that data with the method below, remove
				// any existing information about it, and then insert the
				// new stuff.

				$insertions = $this->getInsertions($critter, $critterId, $property, $propertyId, $table);
				$this->debug($insertions);

				$this->db->delete($table, ["critter_id" => $critterId]);
				$this->db->insert($table, $insertions);
			}
		}

		// there's one set of optional properties for critters that are
		// different in the XML than the others:  qualities.  in the XML
		// they're split into positive and negative lists, so the loop
		// above won't work.  so, we'll create another loop here.

		$this->handleQualities($critter, $critterId);
	}

	/**
	 * @param SimpleXMLElement $critter
	 * @param int              $critterId
	 * @param string           $property
	 * @param string           $propertyId
	 *
	 * @param string           $table
	 *
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getInsertions(SimpleXMLElement $critter, int $critterId, string $property, string $propertyId, string $table) {
		$columns = $this->db->getTableColumns($table);
		$map = $this->getMap($property);

		foreach ($critter->{$property} as $elements) {
			foreach ($elements as $element) {

				// in here, we're looking at a specific, single XML
				// element related to the power, skill, etc. that we're
				// working on.  we'll want to extract its attributes,
				// get it's ID and add that to those, and collect our
				// insertions for the database.

				$insertion = $this->getElementAttributes($element);
				$insertion = array_merge($insertion, [
					$propertyId  => $map[(string) $element],
					"critter_id" => $critterId,
				]);

				// we're almost done.  now we just have to be sure
				// that any column in the table that's not in insertion
				// is set null.

				$insertion = $this->setDefaults($insertion, $columns, $property);


				$insertions[] = $insertion;
			}
		}

		return $insertions ?? [];
	}

	/**
	 * @param string $property
	 *
	 * @return array
	 */
	protected function getMap(string $property): array {

		// most of the time, the properties that we're accessing in the XML
		// document are named the same as the name => ID maps that reside in
		// the properties of this object.  but, for some, we want to return
		// something else.  this switch statement handles it all for us.

		$temp = $property;

		switch ($property) {
			case "optionalpowers":
				$temp = "powers";
				break;

			case "positive":
			case "negative":
				$temp = "qualities";
				break;
		}

		return $this->{$temp};
	}

	/**
	 * @param SimpleXMLElement $element
	 *
	 * @return array
	 */
	protected function getElementAttributes(SimpleXMLElement $element): array {
		$attributes = ((array) $element->attributes())["@attributes"] ?? [];

		// chummer labels the "select" attribute as the description of a
		// power.  i think, elsewhere, it uses that attribute to indicate
		// a selection between options, but in critters.xml, that doesn't
		// seem to be the case.  we'll switch things here to keep the
		// calling scope as clean as possible.

		if (isset($attributes["select"])) {
			$attributes["description"] = $attributes["select"];
			unset($attributes["select"]);
		}

		return $attributes;
	}

	/**
	 * @param array  $destination
	 * @param array  $source
	 * @param string $property
	 *
	 * @return array
	 */
	protected function setDefaults(array $destination, array $source, string $property): array {

		// to set our $source columns to null in $destination, we'll create
		// a $temp array that indexed by $source with null values.  then, we
		// merge that array and $destination.  since $destination is the
		// second array, it's values overwrite $temp.

		$temp = array_fill_keys($source, null);

		if (strpos($property, "powers") !== false) {

			// for both the powers and optionalpowers properties, we'll have
			// to fill-in the ENUM optional column.  since it's not-nullable,
			// we'll set it's default value here.

			$temp["optional"] = $property === "powers" ? "N" : "Y";
		}

		return array_merge($temp, $destination);
	}

	/**
	 * @param SimpleXMLElement $critter
	 * @param int              $critterId
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function handleQualities(SimpleXMLElement $critter, int $critterId): void {

		// qualities are broken down within critters into sub-lists of
		// positive and negative qualities.  that means the above method
		// handling other optional properties won't work out for them.
		// instead, we handle them as follows.

		if ($this->hasProperty($critter, "qualities")) {
			$qualities = $critter->qualities;
			$insertions = [];

			foreach (["positive", "negative"] as $qualityType) {
				if ($this->hasProperty($qualities, $qualityType)) {
					$insertions[] = $this->getInsertions($qualities, $critterId, $qualityType, "quality_id");
				}
			}

			$this->db->delete("critters_qualities", ["critter_id" => $critterId]);

			if (sizeof($insertions) > 0) {
				$this->db->insert("critters_qualities", $insertions);
			}
		}
	}
}

try {
	$parser = new CrittersParser("data/critters.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	$parser->debug($e);
}
