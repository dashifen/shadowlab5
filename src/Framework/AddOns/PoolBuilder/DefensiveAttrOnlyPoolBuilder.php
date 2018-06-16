<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

class DefensiveAttrOnlyPoolBuilder extends AbstractPoolBuilder {
	protected const CONSTITUENTS = [
		"defensive_attribute_id"  => "attribute_id",
		"defensive_other_attr_id" => "other_attr_id",
	];

	/**
	 * @return string
	 */
	function getMissingConstituentsMessage(): string {
		return "Missing one or both defensive attributes.";
	}

	/**
	 * @return int
	 */
	function getMissingConstituentsErrorNumber(): int {
		return PoolBuilderException::MISSING_DEFENSIVE_DATA;
	}


}