<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;

class QualitiesParser extends AbstractParser {
	protected const FREAKISH = [
		"360-degree eyesight",
		"beak",
		"camouflage",
		"functional tail",
		"larger tusks",
		"low-light vision",
		"proboscis",
		"satyr legs",
		"shiva arms",
		"cephalopod skull",
		"cyclopean eye",
		"deformity",
		"feathers",
		"insectoid features",
		"neoteny",
		"scales",
		"third eye",
		"vestigial tail",
	];

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		foreach ($this->xml->qualities->quality as $quality) {
			extract((array) $quality);

			/**
			 * @var string $id
			 * @var string $name
			 * @var string $karma
			 * @var string $category
			 * @var string $source
			 * @var string $page
			 */

			$costs = $this->getMinAndMax($quality);
			if ($this->canContinue($costs)) {
				// since the metagenetic flag is only set for those
				// qualities that require it, we can't rely on the
				// extraction above to get things right; if a prior
				// quality was metagenetic, the flag may still be
				// set here.

				$metagenetic = isset($quality->metagenetic)
					? "Y"
					: "N";

				$data = [
					"minimum" => $costs["minimum"],
					"maximum" => $costs["maximum"] !== "NULL"
						? $costs["maximum"]
						: 0,

					"metagenetic" => $metagenetic,
					"freakish"    => $this->isFreakish($name),
					"book_id"     => $this->bookMap[$source],
					"page"        => $page,
				];

				$insertData = array_merge($data, [
					"quality" => $name,
					"guid"    => strtoupper($id),
				]);

				$this->db->upsert("qualities", $insertData, $data);
			}
		}

		// now that we've done the work above, we have two clean-up
		// queries to run.  some versions of low-light vision are
		// freakish, but others aren't.  we'll fix the ones that
		// aren't as follows:

		$this->db->runQuery("
			UPDATE qualities 
			SET freakish = 'N'
			WHERE quality IN (
				'Low-Light Vision (Changeling)', 
				'Low-Light Vision'
			)
		");

		// and, the PDO library likes to set NULL values to something
		// else even if you use the PDO::PARAM_NULL.  so, we set them to
		// zeros in the loop above, and we set them to NULL here.

		$this->db->runQuery("
			UPDATE qualities 
			SET maximum = NULL 
			WHERE maximum = 0
		");
	}

	/**
	 * @param SimpleXMLElement $quality
	 *
	 * @return array
	 */
	protected function getMinAndMax(SimpleXMLElement $quality): array {
		$minimum = $maximum = "NULL";

		$karma = (string) $quality->karma;

		if (!is_numeric($karma)) {

			// if our $karma cost is not numeric, then it's usually
			// a range. thus we can look for two numbers separated by
			// some sort of non-numeric character(s) and determine a
			// minimum and maximum cost.

			if (!preg_match("/(\d+)\D+(\d+)/", $karma, $matches)) {
				echo "Could not update: $quality->name<br>";
			} else {
				$minimum = $matches[1];
				$maximum = $matches[2];
			}
		} else {
			$minimum = $karma;
		}

		return [
			"minimum" => $minimum,
			"maximum" => $maximum,
		];
	}

	/**
	 * @param array $costs
	 *
	 * @return bool
	 */
	protected function canContinue(array $costs): bool {

		// the costs array is a minimum and maximum value.  we
		// can continue as long as the minimum isn't "NULL."

		return ($costs["minimum"] ?? "") === "NULL";
	}

	/**
	 * @param string $quality
	 *
	 * @return bool
	 */
	protected function isFreakish(string $quality): bool {
		foreach (self::FREAKISH as $freakishQuality) {

			// some of our freakish qualities have additional
			// information after the text included in the constant
			// above.  so, we'll look to see if our $quality can
			// be found within any of the freakish qualities.  if
			// it can be, then we return "Y"

			if (strpos($quality, $freakishQuality) !== false) {
				return true;
			}
		}

		return false;
	}
}

try {
	$parser = new QualitiesParser("data/qualities.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
