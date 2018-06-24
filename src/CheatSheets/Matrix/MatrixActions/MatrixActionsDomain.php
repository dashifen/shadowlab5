<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Dashifen\Database\DatabaseException;
use Shadowlab\Framework\Domain\AbstractDomain;

class MatrixActionsDomain extends AbstractDomain {
	/**
	 * @param bool $view
	 *
	 * @return array [string, string, string]
	 */
	protected function getRecordDetails($view = false): array {

		// this method returns the details necessary to get information
		// related to our record.  our record ID is a matrix action ID
		// within this handler, so we need to tell the abstract layer
		// how to get the rest of the information with that ID giving it
		// that name, the table in which it resides, and the item by
		// which to order when selecting the collection.

		return [
			"matrix_action_id",
			($view ? "matrix_actions_view" : "matrix_actions"),
			"matrix_action",
		];
	}

	/**
	 * @param string $table
	 * @param bool   $withFKOptions
	 *
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getTableDetails(string $table, bool $withFKOptions = true) {

		// our matrix actions are actually rather complex because
		// of their link to the pools table which encapsulate the
		// attributes and skills which are used in conjunction with
		// them. so, we can get the schema for our matrix_actions
		// table pretty easily, but we also need to provide the
		// information related to those pools for our form.

		$additions = $this->getAdditionalSchema();
		$schema = parent::getTableDetails("matrix_actions");
		$schema = $this->addToSchemaAfter($schema, "description", $additions);
		$schema = $this->capitalizeActions($schema);
		$schema = $this->removePools($schema);
		return $schema;
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getAdditionalSchema(): array {

		// now, we need to get information related to our attributes
		// and skills so that we can add the following information to
		// our schema offensive_skill_id, offensive_attribute_id,
		// offensive_limit_id, defensive_attribute_id, and
		// defensive_other_attr_id.

		$additions = [];
		$skillSchema = parent::getTableDetails("skills");
		$attributeSchema = parent::getTableDetails("attributes");
		$defensiveAttributes = $this->getDefensiveAttributes();

		$additions["offensive_skill_id"] = $skillSchema["skill_id"];
		$additions["offensive_skill_id"]["OPTIONS"] = $this->getSkills();

		$additions["offensive_attribute_id"] = $attributeSchema["attribute_id"];
		$additions["offensive_attribute_id"]["OPTIONS"] = $this->getOffensiveAttributes();

		$additions["offensive_limit_id"] = $attributeSchema["attribute_id"];
		$additions["offensive_limit_id"]["OPTIONS"] = $this->getLimits();

		$additions["defensive_attribute_id"] = $attributeSchema["attribute_id"];
		$additions["defensive_attribute_id"]["OPTIONS"] = $defensiveAttributes;

		$additions["defensive_other_attr_id"] = $attributeSchema["attribute_id"];
		$additions["defensive_other_attr_id"]["OPTIONS"] = $defensiveAttributes;

		// now, the last thing we need to do is make all of these fields
		// optional.  that's because the Change Icon action (at least) doesn't
		// require a test.

		$items = ["offensive_skill_id", "offensive_attribute_id",
			"offensive_limit_id", "defensive_attribute_id",
			"defensive_other_attr_id"];

		foreach ($items as $item) {
			$additions[$item]["IS_NULLABLE"] = "YES";
		}

		return $additions;
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getDefensiveAttributes(): array {
		return $this->getAttributes(["mental", "matrix"]);
	}

	/**
	 * @param array $types
	 * @param array $ids
	 *
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getAttributes(array $types, array $ids = []): array {

		// we'll always have types to limit our selection here.  but,
		// sometimes we also get some ID numbers.  we'll create a WHERE
		// clause that always includes the former and includes the
		// latter when necessary.

		$where = "attribute_type IN (:types)";
		$where .= sizeof($ids) > 0 ? " OR attribute_id IN (:ids)" : "";

		// now, handily, our database object can take both types and
		// IDs at all times, and then it just uses what it needs skipping
		// the rest.

		$data = ["types" => $types, "ids" => $ids];
		$attributes = $this->db->getMap("SELECT attribute_id, UCWORDS(attribute)
			FROM attributes WHERE $where ORDER BY attribute", $data);

		return [""] + $attributes;
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getSkills(): array {
		$skills = $this->db->getMap("SELECT skill_id, UCWORDS(skill)
			FROM skills WHERE skill_group_id IN (6, 7, 15) AND deleted=0
			ORDER BY skill");

		return [""] + $skills;
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getOffensiveAttributes(): array {
		return $this->getAttributes(["mental"], [11]);
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function getLimits(): array {
		$limits = $this->db->getMap("SELECT attribute_id, UCWORDS(attribute)
			FROM attributes WHERE attribute_type IN ('matrix')
			OR attribute_id = 25 ORDER BY attribute");

		return [""] + $limits;
	}

	/**
	 * @param array $schema
	 *
	 * @return array
	 */
	protected function capitalizeActions(array $schema): array {

		// for aesthetics, we want to capitalize our actions, so
		// simple becomes Simple and so on.  we also add a blank
		// option to our actions array so that the visitor is forced
		// to make a choice.

		foreach ($schema["action"]["OPTIONS"] as &$action) {
			$action = ucfirst($action);
		}

		$schema["action"]["OPTIONS"] = array_merge(["" => ""],
			$schema["action"]["OPTIONS"]);

		return $schema;
	}

	/**
	 * @param array $schema
	 *
	 * @return array
	 */
	protected function removePools(array $schema): array {

		// this one's simple enough that we could probably have just done
		// it above.  but, this method's single responsibility is simply
		// to unset our pool IDs from $schema and return it.

		unset(
			$schema["offensive_pool_id"],
			$schema["defensive_pool_id"]
		);

		return $schema;
	}

	/**
	 * @param int $recordId
	 *
	 * @return array
	 * @throws DatabaseException
	 */
	protected function readOne(int $recordId): array {
		$sql = $this->getQuery() . " WHERE matrix_action_id = :matrix_action_id";
		return $this->db->getRow($sql, ["matrix_action_id" => $recordId]);
	}

	protected function getQuery(): string {
		return "SELECT matrix_action_id, matrix_action, ma.description,
			offensive_skill_id, offensive_attribute_id, offensive_limit_id,
			defensive_attribute_id, defensive_other_attr_id, marks, action,
			book_id, book, abbreviation, page FROM matrix_actions_view ma ";
	}

	/**
	 * @return array
	 * @throws DatabaseException
	 */
	protected function readAll(): array {
		return $this->db->getResults($this->getQuery() . " ORDER BY matrix_action");
	}

	/**
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	protected function getRecordsTitle(array $records, bool $isCollection): string {
		return $isCollection ? "Matrix Actions" : $records[0]["matrix_action"];
	}
}
