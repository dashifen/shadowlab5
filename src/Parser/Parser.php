<?php

namespace Shadowlab\Parser;

use SimpleXMLElement;

class Parser {
	/**
	 * @var string
	 */
	protected $dataFile;

	/**
	 * @var SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Parser constructor.
	 *
	 * @param string $dataFile
	 *
	 * @throws ParserException
	 */
	public function __construct(string $dataFile = "") {
		if (!empty($dataFile)) {
			$this->setDataFile($dataFile);
			$this->loadDataFile();
		}
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
}