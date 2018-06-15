<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class ComplexFormsParser extends AbstractParser {
	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		if ($this->canContinue()) {
			foreach ($this->xml->complexforms->complexform as $form) {
				$data = [
					"fading"   => (string) $form->fv,
					"target"   => (string) $form->target,
					"duration" => (string) $form->duration,
					"book_id"  => $this->bookMap[(string) $form->source],
					"page"     => (string) $form->page,
				];

				$insertData = array_merge($data, [
					"complex_form" => (string) $form->name,
					"guid" => strtolower((string) $form->id),
				]);

				$this->db->upsert("complex_forms", $insertData, $data);
			}
		}
	}

	/**
	 * @return bool
	 * @throws MysqlException
	 */
	protected function canContinue(): bool {

		// we'll know if we can continue based on whether or not the
		// ENUM columns in the database have the necessary value options.
		// to know this, we first need to get those options out of the
		// XML as follows.

		$canContinue = true;
		$lists = $this->collectEnumValueOptions();
		foreach ($lists as $list => $data) {

			// now, we want to be sure that the spells table can handle it.  each
			// of the lists here matches the name of a column in that table, and
			// we'll want to make sure that the ENUM values therein match our data.

			$enum_values = $this->db->getEnumValues("complex_forms", $list);
			$difference = array_diff($data, $enum_values);
			if (sizeof($difference) !== 0) {
				echo "Must add the following to complex_forms.$list:";
				$this->debug($difference);
				$canContinue = false;
			}
		}

		return $canContinue;
	}

	protected function collectEnumValueOptions() {
		$enumColumns = ["duration", "target"];

		foreach ($this->xml->complexforms->complexform as $form) {

			// for each of the forms in our list, we want to collect the
			// data for the columns in the database listed above.  these
			// columns are named so that they match the properties of our
			// forms.  we use a variable variable to step up the array of
			// our values.

			foreach ($enumColumns as $column) {
				${$column}[] = (string) $form->{$column};
			}
		}

		$values = [];
		foreach ($enumColumns as $column) {

			// now, we want to make sure that our arrays are unique and
			// don't contain empty strings.  we have a filter() method that'll
			// do that for us.  so, for each of our columns, we filter the
			// array we created above (defaulting to the empty string in case
			// something weird happened) and then add the result to the array
			// we return below.

			$values[$column] = $this->filter(($$column ?? []));
		}

		return $values;
	}

	/**
	 * @param array $collection
	 *
	 * @return array
	 */
	protected function filter(array $collection): array {

		// given a collection, this method ensures that it's
		// unique.  and, it makes sure that the items within
		// it aren't empty or made of only whitespace.

		$unique = array_unique($collection);
		return array_filter($unique, function($str) {
			return !empty(trim($str));
		});
	}
}

try {
	$parser = new ComplexFormsParser("data/complexforms.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	$parser->debug($e);
}

echo "done.";
