<?php
require("../../vendor/autoload.php");

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlInterface;
use Dashifen\Exception\Exception;
use Shadowlab\Framework\Database\Database;
use Shadowlab\Parser\AbstractParser;
use Shadowlab\Parser\ParserException;

class AdeptPowersParser extends AbstractParser {
	/**
	 * @var array
	 */
	protected $ways = [];

	/**
	 * AdeptPowersParser constructor.
	 *
	 * @param string         $dataFile
	 * @param MysqlInterface $db
	 *
	 * @throws DatabaseException
	 * @throws ParserException
	 */
	public function __construct(string $dataFile = "", MysqlInterface $db) {
		$this->ways = $db->getMap("SELECT quality, quality_id FROM adept_ways_view");
		parent::__construct($dataFile, $db);
	}

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	public function parse(): void {
		foreach ($this->xml->powers->power as $i => $power) {
			$powerData = $this->getPowerData($power);

			// the insert data has to include our name, key, and the rest of
			// our data.  but, for the updating information, we only use the
			// data.  this allows us to change some of the names of some powers
			// without having them overwritten when we parse things again next
			// time.

			$insertData = array_merge($powerData, [
				"adept_power" => (string) $power->name,
				"guid"        => strtolower((string) $power->id),
			]);

			$this->db->upsert("adept_powers", $insertData, $powerData);

			if (isset($power->adeptwayrequires)) {
				$this->handleAdeptWayInformation($power);
			}
		}
	}

	/**
	 * @param SimpleXMLElement $power
	 *
	 * @return array
	 */
	protected function getPowerData(SimpleXMLElement $power): array {
		$data = [
			"cost"    => (float) $power->points + (float) ($power->extrapointcost ?? 0),
			"levels"  => (string) $power->levels === "true" ? "Y" : "N",
			"action"  => strtolower((string) $power->action),
			"book_id" => $this->bookMap[(string) $power->source],
			"page"    => (int) $power->page,
		];

		if ($data["levels"] === "Y") {
			$data["cost_per_level"] = (float) $power->points;
		}

		if (((int) ($power->maxlevels ?? 0)) !== 0) {
			$data["maximum_levels"] = (int) $power->maxlevels;
		}

		return $data;
	}

	/**
	 * @param SimpleXMLElement $power
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	protected function handleAdeptWayInformation(SimpleXMLElement $power): void {
		$key = ["guid" => strtolower((string) $power->id)];
		$adept_power_id = $this->db->getVar("SELECT adept_power_id FROM adept_powers WHERE guid = :guid", $key);

		$this->db->delete("adept_powers_ways", [
			"adept_power_id" => $adept_power_id,
		]);

		$insertions = $this->getWayInsertions($power, $adept_power_id);

		if (sizeof($insertions) > 0) {
			$this->db->insert("adept_powers_ways", $insertions);
		}
	}

	/**
	 * @param SimpleXMLElement $power
	 * @param int              $adept_power_id
	 *
	 * @return array
	 */
	protected function getWayInsertions(SimpleXMLElement $power, int $adept_power_id): array {
		foreach ($power->adeptwayrequires->required->oneof->quality as $quality) {
			$quality_id = $this->ways[(string) $quality];
			if (in_array($quality_id, [135, 138, 140])) {

				// these three ways (beast, magician, and spiritual
				// respectively) are handled in chummer differently than
				// the others due to their ability to "choose another power"
				// for a discount.  the other ways are fine, but for these
				// we'll use the following to see if we add this power/way
				// pair to the $insertions array.  note: since magician's way
				// folk choose anything except Improved Reflexes, they get
				// an empty array.

				if ($quality_id == 135) {
					$powers = [2, 30, 4, 38, 7, 16, 50, 20, 54, 25];
				} elseif ($quality_id == 140) {
					$powers = [2, 38, 39, 7, 16, 50, 20];
				} else {
					$powers = [];
				}

				if (!in_array($adept_power_id, $powers)) {

					// now, if the power we're currently looking at isn't
					// in the approved list, we'll just continue.

					continue;
				}
			}

			$insertions[] = [
				"adept_power_id" => $adept_power_id,
				"quality_id"     => $quality_id,
			];
		}

		return $insertions ?? [];
	}
}

try {
	$parser = new AdeptPowersParser("data/powers.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}


$actions = [];
$allProperties = [];


echo "done.";
