<?php
require("../../vendor/autoload.php");

use Shadowlab\Parser\AbstractParser;
use Shadowlab\Framework\Database\Database;
use Dashifen\Database\Mysql\MysqlException;
use Dashifen\Database\DatabaseException;
use Dashifen\Exception\Exception;

class ProgramsParser extends AbstractParser {

	/**
	 * @return void
	 * @throws MysqlException
	 * @throws DatabaseException
	 */
	public function parse(): void {
		$this->updateCategoryTable("programs", "program_type", "programs_types");
		$types = $this->db->getMap("SELECT program_type, program_type_id FROM programs_types");

		foreach ($this->xml->programs->program as $program) {
			$data = [
				"availability"    => (string) $program->avail,
				"max_rating"      => (string) ($program->rating ?? 0),
				"program_type_id" => $types[(string) $program->category],
				"book_id"         => $this->bookMap[(string) $program->source],
				"page"            => (int) $program->page,
			];

			// the insert data for must include the programs name and guid
			// as well as the program information we collected in $data.
			// but, if we already inserted this program, then all we want
			// to do is update its $data.

			$insertData = array_merge($data, [
				"program" => (string) $program->name,
				"guid"    => strtolower((string) $program->id),
			]);

			$this->db->upsert("programs", $insertData, $data);
		}
	}
}

try {
	$parser = new ProgramsParser("data/programs.xml", new Database());
	$parser->parse();
} catch (Exception $e) {
	if ($e instanceof DatabaseException) {
		echo "Failed: " . $e->getQuery();
	}

	$parser->debug($e);
}
