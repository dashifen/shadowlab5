<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

class OffensiveAttrSkillPoolBuilder extends AbstractPoolBuilder {
	protected const CONSTITUENTS = [
		"offensive_skill_id"     => "skill_id",
		"offensive_attribute_id" => "attribute_id",
		"offensive_limit_id"     => "limit_id",
	];

	/**
	 * @return string
	 */
	function getMissingConstituentsMessage(): string {
		return "Missing offensive skill, attribute, and/or limit.";
	}

	/**
	 * @return int
	 */
	function getMissingConstituentsErrorNumber(): int {
		return PoolBuilderException::MISSING_DEFENSIVE_DATA;
	}

}