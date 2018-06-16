<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

class DefensiveAttrSkillPoolBuilder extends AbstractPoolBuilder {
	protected const CONSTITUENTS = [
		"defensive_skill_id"     => "skill_id",
		"defensive_attribute_id" => "attribute_id",
		"defensive_limit_id"     => "limit_id",
	];

	/**
	 * @return string
	 */
	function getMissingConstituentsMessage(): string {
		return "Missing defensive attribute, skill, and/or limit.";
	}

	/**
	 * @return int
	 */
	function getMissingConstituentsErrorNumber(): int {
		return PoolBuilderException::MISSING_DEFENSIVE_DATA;
	}
}