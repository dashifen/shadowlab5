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
 * down.  Once we get past the CRUD related tantrums, we see some ways
 * that we interact with the database specifically to understand the
 * schema of the database tables that we interact with.
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
		
		// when working with our data, sometimes it's nice to know about that
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
		
		// what the above statement can't do for us is get the information
		// about viable options for the values within our database columns.
		// some types -- like enums and sets -- have very specific limitations.
		// other times, our integer fields may be links to other database
		// tables where we gather values based on foreign key relationships.
		// this loop helps us find those options.
		
		foreach ($schema as $column => &$columnData) {
			$columnData["OPTIONS"] = $this->getColumnOptions($table, $column, $columnData["DATA_TYPE"]);
		}
		
		return $schema;
	}
	
	/**
	 * @param string $table
	 * @param string $column
	 * @param string $dataType
	 *
	 * @return array
	 */
	protected function getColumnOptions(string $table, string $column, string $dataType): array {
		$options = [];
		
		// there are two groups of types that we want to mess with here:
		// enums (and sets) and any integer type.  text based columns are
		// free-formed, after all, so we don't worry about them at this
		// time.  once we determine if there's any work to be done here,
		// we call one of the following methods.
		
		if (in_array($dataType, ["enum", "set"])) {
			$options = $this->getEnumOptions($table, $column);
		} elseif (strpos($dataType, "int") !== false) {
			$options = $this->getFKOptions($table, $column);
		}

		return $options;
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
			$options = $this->db->getMap("SELECT $fkColumn, $fkColumnData FROM $fkTable");
		}
		
		return $options;
	}
}
