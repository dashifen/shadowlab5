<?php

namespace Shadowlab\Parser;

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
	 * Parser constructor.
	 *
	 * @param string         $dataFile
	 * @param MysqlInterface $db
	 *
	 * @throws ParserException
	 */
	public function __construct(string $dataFile = "", MysqlInterface $db) {
		if (!empty($dataFile)) {
			$this->setDataFile($dataFile);
			$this->loadDataFile();
		}

		$this->db = $db;
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
	 * @throws MysqlException
	 */
	abstract public function parse(): void;
}