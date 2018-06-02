<?php

namespace Shadowlab\Parser;

use Dashifen\Database\DatabaseException;
use Dashifen\Database\Mysql\MysqlException;
use SimpleXMLElement;
use Dashifen\Database\Mysql\MysqlInterface;

abstract class AbstractParser {
	/**
	 * @var string
	 */
	protected $dataFile;

	/**
	 * @var SimpleXMLElement
	 */
	protected $xml;

	/**
	 * @var MysqlInterface
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $bookMap;

	/**
	 * Parser constructor.
	 *
	 * @param string         $dataFile
	 * @param MysqlInterface $db
	 *
	 * @throws ParserException
	 * @throws DatabaseException
	 */
	public function __construct(string $dataFile = "", MysqlInterface $db) {
		if (!empty($dataFile)) {
			$this->setDataFile($dataFile);
			$this->loadDataFile();
		}

		$this->db = $db;
		$this->getBooksMap();
	}

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	protected function getBooksMap(): void {
		$this->bookMap = $this->db->getMap("SELECT abbreviation, book_id FROM books");
	}

	/**
	 * @param string $dataFile
	 *
	 * @return void
	 * @throws ParserException
	 */
	public function setDataFile(string $dataFile): void {
		if (!is_file($dataFile)) {
			throw new ParserException("File note found: $dataFile", ParserException::FILE_NOT_FOUND);
		}

		$this->dataFile = $dataFile;
	}

	/**
	 * @return bool
	 * @throws ParserException
	 */
	public function loadDataFile(): bool {
		try {
			$data = file_get_contents($this->dataFile);
			$this->xml = new SimpleXMLElement($data);
			return true;
		} catch (\Exception $e) {
			throw new ParserException("Bad XML", ParserException::BAD_XML, $e);
		}
	}

	/**
	 * @param mixed ...$x
	 */
	public function debug(...$x) {
		$dumps = [];
		foreach ($x as $y) {
			$dumps[] = print_r($y, true);
		}

		echo "<pre>" . join("</pre><pre>", $dumps) . "</pre>";
	}

	/**
	 * @return void
	 * @throws DatabaseException
	 */
	abstract public function parse(): void;

	/**
	 * @param string $plural
	 * @param string $key
	 * @param string $table
	 *
	 * @return void
	 */
	protected function updateCategoryTable(string $plural, string $key, string $table): void {
		if (isset($this->xml->{$plural})) {

			// if our $plural property is set, and it should be or we wouldn't
			// have called this function, then we'll get its children.  looping
			// over them will tell us what we need to insert into the database

			$children = $this->xml->{$plural}->children();

			try {

				// for each of our children, we insert.  the $key is expected
				// to be a UNIQUE column, so re-inserting an old category will
				// simply fail.

				foreach ($children as $child) {
					$this->db->insert($table, [
						$key => (string) $child
					]);
				}
			} catch (DatabaseException $e) {

				// it's assumed that the above loop will encounter previously
				// inserted categories and, therefore, throw exceptions.  but,
				// since we expect that to happen, we'll just ignore them and
				// hope that one isn't something other than a re-insertion
				// warning.

			}
		}
	}
}