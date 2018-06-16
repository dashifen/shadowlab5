<?php

namespace Shadowlab\Framework\AddOns\Searchbar;

use Dashifen\Searchbar\SearchbarInterface as SBInterface;

interface SearchbarInterface extends SBInterface {
	/**
	 * @param string $label
	 *
	 * @return mixed
	 */
	public function addReset(string $label = '<i class="fa fa-fw fa-undo" aria-hidden="true" title="Reset"></i>');
}
