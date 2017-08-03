<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\AbstractMysqlDomain;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\DomainException;

/**
 * Class Domain
 *
 * This default Domain implementation for the Shadowlab simply throws a
 * giant tantrum all of the place.  Floor kicking.  Screaming.  All the
 * good stuff.  Children can overwrite these methods to help calm things
 * down.
 *
 * @package Shadowlab\Framework\Domain
 */
class Domain extends AbstractMysqlDomain {
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function create(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Create",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function read(array $data = []): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Read",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function update(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Update",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function delete(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Delete",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param string $table
	 *
	 * @return array
	 */
	protected function getTableDetails(string $table) {
		
		// when transforming data, sometimes it's nice to know about that
		// database table from which (or into which) the data is coming (or
		// going).  this method gets that for us.
		
		$statement = <<< SCHEMA
			SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE,
				DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, EXTRA
			
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = 'shadowlab'
			AND TABLE_NAME = :table
SCHEMA;
		
		$schema = $this->db->getMap($statement, ["table" => $table]);
		
		foreach ($schema as $column => &$columnData) {
			$type = $columnData["DATA_TYPE"];
			
			// in this loop we want to look for columns of the enum or set
			// type.  if we find them, we get their values.  we also look for
			// any integer columns and check to see if they reference any
			// foreign keys.  if so, we'll also want to get their possible
			// values.
			
			$columnData["OPTIONS"] = [];
			
			if (in_array($type, ["enum", "set"])) {
				$columnData["OPTIONS"] = $this->getEnumOptions($table, $column);
			} elseif (strpos($type, "int") !== false) {
				$columnData["OPTIONS"] = $this->getFKOptions($table, $column);
			}
		}
		
		return $schema;
	}
	
	/**
	 * @param string $table
	 * @param string $column
	 *
	 * @return array
	 */
	protected function getEnumOptions(string $table, string $column): array {
		
		// this one's easy; we have a function that does all the work
		// for us as a part of the database property as follows.  then,
		// we use that returned information as both the keys and values
		// of our options.
		
		$options = $this->db->getEnumValues($table, $column);
		return array_combine($options, $options);
	}
	
	/**
	 * @param string $table
	 * @param string $column
	 *
	 * @return array
	 */
	protected function getFKOptions(string $table, string $column): array {
		$options = [];

		// for any of our integer types (e.g. int, tinyint, etc.) we
		// want to see if they're referencing another table as a part
		// of a foreign key (FK).
		
		$statement = <<< CONSTRAINT
			SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = :database
			AND TABLE_NAME = :table
			AND COLUMN_NAME = :column
CONSTRAINT;
		
		$fkConstraint = $this->db->getRow($statement, [
			"database" => $this->db->getDatabase(),
			"table"    => $table,
			"column"   => $column
		]);
		
		list($fkTable, $fkColumn) = array_values($fkConstraint);
		
		if (strlen($fkTable) > 0) {
			
			// if we identified a FK relationship for this column, we need
			// to know the name of the _other_ column in the database table.
			// we can get all of its columns, remove the $fkColumn and then
			// we get the remaining column.
			
			$columns = $this->db->getTableColumns($fkTable);
			$fkColumnData = array_diff($columns, [$fkColumn])[0];
			$statement = "SELECT $fkColumn, $fkColumnData FROM $fkTable";
			$options = $this->db->getMap($statement);
		}
		
		return $options;
	}
}
