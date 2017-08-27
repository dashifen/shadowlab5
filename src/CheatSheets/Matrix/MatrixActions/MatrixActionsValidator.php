<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Shadowlab\Framework\Domain\AbstractValidator;

class MatrixActionsValidator extends AbstractValidator {
	/**
	 * @param array  $posted
	 * @param array  $schema
	 * @param string $action
	 *
	 * @return bool
	 */
    protected function checkForOtherErrors(array $posted, array $schema, string $action): bool {

        // the errors we have to check for here all relate to our pools.
        // there are three items that make up our offensive pool and two in
        // our defensive one.  granted, these five items are optional, but
        // if we have any single value within the two sets that make up our
        // offensive and defensive pools, then the rest are required.

        $offensive = ["offensive_attribute_id", "offensive_skill_id", "offensive_limit_id"];
        $defensive = ["defensive_attribute_id", "defensive_other_attr_id"];
        $pools = ["offensive" => $offensive, "defensive" => $defensive];

        $foundError = false;
        foreach ($pools as $pool => $indices) {
            if ($this->checkPool($posted, $indices)) {
                $errorIndex = $pool . "_attribute_id";

                // as long as we know that we have data within this pool that
                // we need to check, we'll do so.  first, for both pools, we
                // want to see that they're complete.

                if ($this->anyEmpty($posted, $indices)) {
                    $this->validationErrors[$errorIndex] = "Please select the entire $pool pool.";
                    $foundError = true;
                }

                // then, for the defensive pool only, we need ot be sure that
                // they didn't pick the same attribute twice.

               elseif ($pool === "defensive" && $this->poolMatches($posted, $indices)) {
                    $this->validationErrors[$errorIndex] = "Please select different attributes for this pool.";
                    $foundError = true;
                }
            }
        }

        return $foundError;
    }

    /**
     * @param array $posted
     * @param array $indices
     *
     * @return bool
     */
    protected function checkPool(array $posted, array $indices): bool {

        // to know if we need to check our pool for a problem, we simple
        // look to see if any of our $indices are not empty within $posted.
        // the first time we find one, we can return true and save a little
        // bit of time.

        foreach ($indices as $index) {
            if (!empty($posted[$index])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $posted
     * @param array $indices
     *
     * @return bool
     */
    protected function anyEmpty(array $posted, array $indices): bool {

        // this is essentially the opposite of the above function.  that one
        // returns true when it finds an index that isn't empty, this one does
        // so when it finds an empty one.

        foreach ($indices as $index) {
            if (empty($posted[$index])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $posted
     * @param array $indices
     *
     * @return bool
     */
    protected function poolMatches(array $posted, array $indices): bool {

        // this function needs to see if the $indices within $posted contain
        // the same values.  first, we extract those specified values within
        // $posted.  then, we can use array_count_values() see how many of
        // each value there is in the array.  if any value in the array has
        // the same count as the size of it, then we have a problem.

        $temp = array_filter($posted, function($x) use ($indices) {
            return in_array($x, $indices);
        }, ARRAY_FILTER_USE_KEY);

        $tempSize = sizeof($temp);
        $counts = array_count_values($temp);
        foreach ($counts as $count) {
            if ($count === $tempSize) {
                return true;
            }
        }

        return false;
    }
}
