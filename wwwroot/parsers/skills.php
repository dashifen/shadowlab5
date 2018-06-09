<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class SkillsParser extends AbstractParser {
	protected $groups = [];
	protected $categories = [];

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		$this->updateSkillGroups();
		$this->updateSkillCategories();
		$this->groups = $this->db->getMap("SELECT skill_group, skill_group_id FROM skill_groups");
		$this->categories = $this->db->getMap("SELECT skill_category, skill_category_id FROM skills_categories");

		foreach ($this->xml->skills->skill as $skill) {
			$data = $this->getSkillData($skill);

			$insertData = array_merge($data, [
				"guid"  => strtolower((string) $skill->id),
				"skill" => (string) $skill->name,
			]);

			$this->db->upsert("skills", $insertData, $data);
			$this->updateSpecializations($skill);
		}
	}

	/**
	 * TODO:  convert this to updateCategoryTable()?
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function updateSkillGroups(): void {
		$statement = "SELECT skill_group_id FROM skill_groups
			WHERE skill_group = :skill_group";

		foreach ($this->xml->skillgroups->name as $skillGroup) {
			$key = ["skill_group" => $this->groupNameConvert($skillGroup)];
			$skillGroupId = $this->db->getVar($statement, $key);

			if (!is_numeric($skillGroupId)) {
				$this->db->insert("skill_groups", $key);
			}
		}
	}

	/**
	 * @param SimpleXMLElement $skillGroup
	 *
	 * @return string
	 */
	protected function groupNameConvert(SimpleXMLElement $skillGroup): string {

		// for some reason, we were passing skill groups through
		// strtolower().  i don't remember why, so i removed it.

		return ((string) $skillGroup);
	}

	/**
	 * TODO:  Convert this to updateCategoryTable()?
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function updateSkillCategories(): void {
		$statement = "
			SELECT skill_category_id
			FROM skills_categories 
			WHERE skill_category = :skill_category
			AND skill_category_type = :skill_category_type
		";

		foreach ($this->xml->categories->category as $category) {
			/** @var SimpleXMLElement $category */

			$key = [
				"skill_category"      => $this->categoryNameConvert($category),
				"skill_category_type" => (string) ($category->attributes()["type"]),
			];

			$skillCategoryId = $this->db->getVar($statement, $key);

			if (!is_numeric($skillCategoryId)) {
				$this->db->insert("skills_categories", $key);
			}
		}
	}

	/**
	 * @param SimpleXMLElement $category
	 *
	 * @return string
	 */
	protected function categoryNameConvert(SimpleXMLElement $category): string {
		return strtolower(str_replace(" Active", "", (string) $category));
	}

	/**
	 * @param SimpleXMLElement $skill
	 *
	 * @return array
	 */
	protected function getSkillData(SimpleXMLElement $skill): array {
		$data = [
			"skill_category_id" => $this->categories[$this->categoryNameConvert($skill->category)],
			"can_default"       => ((string) $skill->default) === "Yes" ? "Y" : "N",
			"is_exotic"         => ((string) $skill->default) === "Yes" ? "Y" : "N",
			"book_id"           => $this->bookMap[(string) $skill->source],
			"page"              => (int) $skill->page,
		];

		// chummer's pseudo-magical skill category is dumb.  we'll
		// just put those into the magical category as follows.

		if ($data["skill_category_id"] == 5) {
			$data["skill_category_id"] = 4;
		}

		// and, if the skillgroup is set and not-empty, then we'll
		// want to add it to our $data as well.

		if (isset($skillgroup) && strlen($skillgroup) > 0) {
			$data["skill_group_id"] = $this->groups[$this->groupNameConvert($skill->skillgroup)];
		}

		return $data;
	}

	/**
	 * @param SimpleXMLElement $skill
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function updateSpecializations(SimpleXMLElement $skill): void {
		$skillId = $this->db->getVar(
			"SELECT skill_id FROM skills WHERE guid = :guid",
			["guid" => strtolower((string) $skill->id)]
		);

		$this->db->delete("skills_specializations", ["skill_id" => $skillId]);

		if ($this->hasProperty($skill, "specs")) {
			$insertions = [];

			foreach ($skill->specs->children() as $spec) {
				$insertions[] = [
					"skill_id"       => $skillId,
					"specialization" => (string) $spec,
				];
			}

			$this->db->insert("skills_specializations", $insertions);
		}
	}
}

try {
	$parser = new SkillsParser("data/skills.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
