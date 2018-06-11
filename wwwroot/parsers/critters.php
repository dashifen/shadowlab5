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
		$this->skillGroups = $this->db->getMap("SELECT skill_group, skill_group_id FROM skill_groups");
		$this->qualities = $this->db->getMap("SELECT quality, quality_id FROM qualities");
		$this->programs = $this->db->getMap("SELECT program, program_id FROM programs");
		$this->powers = $this->db->getMap("SELECT critter_power, critter_power_id FROM critter_powers");
		$this->skills = $this->getSkillsMap();
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getSkillsMap(): array {

		// for our skills, we want to get the normal list of skills like we do
		// above, but we also need to add skill groups to it.  but, where
		// skills are mapped directly to their ID number, groups map to the
		// skills that are in them.

		$skills = $this->db->getMap("SELECT skill, skill_id FROM skills");
		$groups = $this->db->getMap("SELECT skill_group, GROUP_CONCAT(skill)
			FROM skill_groups INNER JOIN skills USING (skill_group_id)");

		foreach ($groups as $group => $groupSkills) {

			// the GROUP_CONCAT() function returns the list of skills as a
			// comma separated values string.  we'll explode it into an array
			// of skill names, and then add it to skills.  this allows us to
			// find, for example, Athletics in the skills array linked to
			// Running, Gymnastics, and Swimming.

			$skills[$group] = explode(",", $groupSkills);
		}

		return $skills;
	}

	/**
	 * @return void
	 * @throws DatabaseException
	 * @throws ParserException
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
	 * @throws ParserException
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
				$insertions = $this->mergeInsertions($insertions, $propertyId);
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
	 * @throws ParserException
	 */
	protected function getInsertions(SimpleXMLElement $critter, int $critterId, string $property, string $propertyId, string $table) {
		$columns = $this->db->getTableColumns($table);

		foreach ($critter->{$property} as $elements) {
			foreach ($elements as $element) {

				// in here, we're looking at a specific, single XML
				// element related to the power, skill, etc. that we're
				// working on.  we'll want to extract its attributes,
				// and use them to get our ID or IDs for insertion into
				// the database.

				$insertion = $this->getElementAttributes($element);
				$propertyIds = $this->getPropertyIds($element, $property);
				foreach ($propertyIds as $propertyId) {

					// most of the time, we get a single ID value in an array.
					// but for skill groups, we might get multiple IDs; hence,
					// the need for this loop.

					$modifiedInsertion = array_merge($insertion, [
						$propertyId  => $propertyId,
						"critter_id" => $critterId,
					]);

					// we're almost done.  now we just have to be sure that
					// any column in the table that's not in insertion is
					// set null.

					$modifiedInsertion = $this->setDefaults($modifiedInsertion, $columns, $property);
					$insertions[] = $modifiedInsertion;
				}
			}
		}

		return $insertions ?? [];
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
	 * @param SimpleXMLElement $element
	 * @param string           $property
	 *
	 * @return array
	 * @throws ParserException
	 */
	protected function getPropertyIds(SimpleXMLElement $element, string $property) {
		$map = $this->getMap($property);

		// most of the time, our map links properties directly to their ID
		// number.  but, for skill groups, the map is from the group name to
		// the skills within the group.  so, when we grab a value out of our
		// map, if it's numeric, we can return it right away.  but, when we
		// do so, we do it as an array so it matches the return type for
		// the skill group case.

		$propertyId = $map[(string) $element];
		if (is_numeric($propertyId)) {
			return [$propertyId];
		}

		// if it wasn't an number, then it better be an array.  but, we can't
		// return it directly because it's an array of names and we want
		// numbers.  so, we'll loop over it and use our map to convert those
		// names into their mapped ID numbers.

		if (!is_array($propertyId)) {
			throw new ParserException("Unable to find property ID for $property");
		}

		$propertyIds = [];
		foreach ($propertyId as $name) {
			$propertyIds[] = $map[$name];
		}

		return $propertyIds;
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
	 * @param array  $insertions
	 * @param string $propertyId
	 *
	 * @return array
	 * @throws ParserException
	 */
	protected function mergeInsertions(array $insertions, string $propertyId): array {

		// sometimes critters are listed with the same power, etc. twice
		// with different descriptions.  here, we look for that problem and,
		// if we find it, we fix it.

		$duplicates = $this->getDuplicates($insertions, $propertyId);

		if (sizeof($duplicates) > 0) {

			// if we have duplicates, then we need to merge them.  the
			// keys of our $duplicates array tell us which properties were
			// duplicated.  first, we split the array into duplicates and
			// not duplicates.

			$duplicatedIds = array_keys($duplicates);
			$singles = $this->filterUnduplicated($insertions, $propertyId, $duplicatedIds);
			$duplicates = $this->filterDuplicated($insertions, $propertyId, $duplicatedIds);
			$merged = $this->mergeDuplicates($duplicates, $duplicatedIds, $propertyId);
			$insertions = array_merge($singles, $merged);
		}

		return $insertions;
	}

	/**
	 * @param array  $insertions
	 * @param string $propertyId
	 *
	 * @return array
	 */
	protected function getDuplicates(array $insertions, string $propertyId): array {

		// the insertions array has a column named for this set of properties.
		// we can extract that and then count the values within it.  if any
		// of those counts are greater than one, that means that the property
		// is duplicated.

		$propertyIds = array_column($insertions, $propertyId);
		$valueCounts = array_count_values($propertyIds);
		return array_filter($valueCounts, function($count) {
			return $count > 1;
		});
	}

	/**
	 * @param array  $insertions
	 * @param string $propertyId
	 * @param array  $duplicateIds
	 *
	 * @return array
	 */
	protected function filterUnduplicated(array $insertions, string $propertyId, array $duplicateIds): array {
		return array_filter($insertions, function($insertion) use ($propertyId, $duplicateIds) {

			// if one of our insertions is not duplicated, then it's property
			// ID will not be found within the $duplicateIds array that is in
			// scope here via closure.

			return !in_array($insertion[$propertyId], $duplicateIds);
		});
	}

	/**
	 * @param array  $insertions
	 * @param string $propertyId
	 * @param array  $duplicateIds
	 *
	 * @return array
	 */
	protected function filterDuplicated(array $insertions, string $propertyId, array $duplicateIds): array {
		return array_filter($insertions, function($insertion) use ($propertyId, $duplicateIds) {

			// this is the opposite of the prior method.  which means
			// we can perform the same basic operation but return the
			// insertions in which our property id _is_ within the list
			// of duplicate IDs.

			return in_array($insertion[$propertyId], $duplicateIds);
		});
	}

	/**
	 * @param array  $duplicates
	 * @param array  $duplicatedIds
	 * @param string $propertyId
	 *
	 * @return array
	 * @throws ParserException
	 */
	protected function mergeDuplicates(array $duplicates, array $duplicatedIds, string $propertyId): array {

		// finally, we're looking at just the insertion data that is
		// duplicated within the $insertions array that we're working on.
		// the $duplicatedIds array lets us work on each of them one at
		// a time.  then, we merge the descriptions of our duplicates to
		// to remove fix the problem.

		foreach ($duplicatedIds as $id) {
			$temp = array_filter($duplicates, function($duplicate) use ($id, $propertyId) {
				return (int) $duplicate[$propertyId] === $id;
			});

			$descriptions = $this->getDescriptions($temp);
			if (sizeof($descriptions) === 0) {
				throw new ParserException("Attempt to merge $propertyId without descriptions");
			}

			$description = join(", ", $descriptions);
			$merged[] = array_merge(array_shift($temp), [
				"description" => $description
			]);
		}

		return $merged ?? [];
	}

	/**
	 * @param array $duplicates
	 *
	 * @return array
	 */
	protected function getDescriptions(array $duplicates): array {


		foreach ($duplicates as $duplicate) {
			$descriptions[] = $duplicate["description"];
		}

		return $descriptions ?? [];
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
					$insertions[] = $this->getInsertions($qualities, $critterId, $qualityType, "quality_id", "critters_qualities");
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
