<?php

namespace Shadowlab\Framework\AddOns\PoolBuilder;

class OffensiveAttrOnlyPoolBuilder extends AbstractPoolBuilder {
	protected const CONSTITUENTS = [
		"offensive_attribute_id"  => "attribute_id",
		"offensive_other_attr_id" => "other_attr_id",
	];

	/**
	 * @return string
	 */
	function getMissingConstituentsMessage(): string {
		return "Missing one or both attributes in pool.";
	}

	/**
	 * @return int
	 */
	function getMissingConstituentsErrorNumber(): int {
		return PoolBuilderException::MISSING_OFFENSIVE_DATA;
	}
}