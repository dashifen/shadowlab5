<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\AbstractMysqlDomain;
use Dashifen\Domain\DomainException;
use Dashifen\Domain\Payload\PayloadInterface;

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
abstract class AbstractDomain extends AbstractMysqlDomain {
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
	 */
	public function read(array $data = []): PayloadInterface {
		
		// when we're reading information, we need to be sure that any
		// record ID that we get from our Action exists in the database.
		// so, we'll get the list of those IDs and pass them and our $data
		// over to the validator.
		
		$records = $this->getRecords();
		$validationData = array_merge($data, ["records" => $records]);
		if ($this->validator->validateRead($validationData)) {
			
			// if things are valid, then we want to either read the entire
			// collection of our qualities or the single one that's specified.
			// as long as that works, we'll add on the next quality to be
			// described and call it a day.
			
			$isCollection = empty($data["recordId"] ?? "");
			
			$records = !$isCollection
				? $this->readOne($data["recordId"])
				: $this->readAll();
			
			$count = sizeof($records);
			$payload = $this->payloadFactory->newReadPayload($count > 0, [
				"title"   => $this->getRecordsTitle($records, $isCollection),
				"records" => $records,
				"count"   => $count,
			]);
			
			if ($payload->getSuccess()) {
				$payload->setDatum("nextId", $this->getNextId());
				return $this->transformer->transformRead($payload);
			}
		}
		
		// if we didn't create a payload inside the if-block above, then
		// we do so here.  this avoids needed extra else's because of the
		// two ifs in the above block.  with the beatific null coalescing
		// operator, we can do this all as a single statement.
		
		return $this->payloadFactory->newReadPayload(false, [
			"errors" => $this->validator->getValidationErrors(),
		]);
	}
	
	/**
	 * when we getRecords(), we want a list of the records against which
	 * we test ID numbers to determine whether or not we can read information
	 * from the database.
	 *
	 * @return array
	 */
	abstract protected function getRecords(): array;
	
	/**
	 * this method simply grabs a single record from the database based
	 * on the ID number.
	 *
	 * @param int $recordId
	 *
	 * @return array
	 */
	abstract protected function readOne(int $recordId): array;
	
	/**
	 * this one gets all records in a collection from the database and
	 * returns them all at once.
	 *
	 * @return array
	 */
	abstract protected function readAll(): array;
	
	/**
	 * this one simply returns the title for our records (e.g. spells or
	 * qualities or vehicles).  at this level, we don't know what we're
	 * working with, but
	 *
	 * @param array $records
	 * @param bool  $isCollection
	 *
	 * @return string
	 */
	abstract protected function getRecordsTitle(array $records, bool $isCollection): string;
	
	/**
	 * for the purposes of quickly identifying where to start updating
	 * the database with new information, this one gets the next ID
	 * number from the database
	 *
	 * @return int
	 */
	abstract protected function getNextId(): int;
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	public function update(array $data): PayloadInterface {
		
		// updates are a two stage process:  we read data, then we save the
		// changes to it.  we can tell what we're doing here based on the
		// existence of posted data.
		
		$method = !isset($data["posted"]) ? "getDataToUpdate" : "savePostedData";
		return $this->{$method}($data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	protected function getDataToUpdate(array $data): PayloadInterface {
		
		// getting our data to update is a special kind of read action.
		// luckily, we have methods to handle that already ready already.
		
		$records = $this->getRecords();
		$validationData = array_merge($data, ["records" => $records]);
		if ($this->validator->validateUpdate($validationData)) {
			
			// having confirmed that we can read a record from the
			// database for the purposes of an update, it's actually
			// time to do so.  then, if that works out, we'll add in
			// the information about the database table, and send it
			// all back to our action.
			
			$records = $this->readOne($data["recordId"]);
			$payload = $this->payloadFactory->newUpdatePayload(sizeof($records) > 0, [
				"records" => $records
			]);
			
			if ($payload->getSuccess()) {
				$payload->setDatum("schema", $this->getTableDetails($data["table"]));
				return $payload;
			}
		}
		
		return $this->payloadFactory->newUpdatePayload(false, [
			"errors" => $this->validator->getValidationErrors(),
		]);
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
			"column"   => $column,
		]);
		
		if (sizeof($fkConstraint) > 0) {
			list($fkTable, $fkColumnId) = array_values($fkConstraint);
			if (strlen($fkTable) > 0) {
				
				// if we identified a FK relationship for this column, we need
				// to know the name of the _other_ column in the database table.
				// we can get all of its columns, remove the $fkColumnId and then
				// we get the remaining column.
				
				$columns = $this->db->getTableColumns($fkTable);
				$fkColumnData = array_diff($columns, [$fkColumnId]);
				$fkColumn = array_shift($fkColumnData);
				
				$options = $this->db->getMap("SELECT $fkColumnId, $fkColumn FROM $fkTable");
				asort($options);
			}
		}
		
		return $options;
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	protected function savePostedData(array $data): PayloadInterface {
		
		// since we need to know our table before we call our validator,
		// we'll test for its existence here and throw an exception if we
		// don't find it.
		
		$table = $data["table"] ?? null;
		
		if (is_null($table)) {
			throw new DomainException("Cannot save data without table.");
		}
		
		// to validate our posted data, we need to know more about the
		// table into which we're going to be inserting it.  so, we'll
		// get its schema and then pass everything over to our validator.
		
		$schema = $this->getTableDetails($table);
		$validationData = array_merge($data, ["schema" => $schema]);
		if ($this->validator->validateUpdate($validationData)) {
			
			// if we've validated our data, we're ready to put it into the
			// database.  right now, our posted data is a mix of the book_id
			// and the rest of the data that we want to update.  we'll grab
			// the ID and then use array_filter() to collapse the rest of it
			// into the data for our update.
			
			$idName = $data["idName"];
			$posted = $data["posted"];
			$recordId = $posted[$idName];
			$record = array_filter($posted, function($index) use ($idName) {
				return $index !== $idName;
			}, ARRAY_FILTER_USE_KEY);
			
			// at this point, we can pass our record and table data over
			// to our transformer so that it can handle any changes that
			// it needs to do before we we save our records.
			
			$payload = $this->payloadFactory->newUpdatePayload(true, [
				"schema" => $schema,
				"record" => $record,
			]);
			
			$payload = $this->transformer->transformUpdate($payload);
			
			// we'll pass the saving process over to our saveRecord method
			// as follows.  this is to allow children to overwrite things if
			// they need to.
			
			$this->saveRecord($table, $payload, [$idName => $recordId]);
			
			// to know the name of this item within our $record, we can
			// remove the "_id" part of our ID's name, and that leaves
			// the record's name.  for example, adept_power_id becomes
			// simply adept_power.
			
			$recordName = str_replace("_id", "", $idName);
			return $this->payloadFactory->newUpdatePayload(true, [
				"title"  => $posted[$recordName],
				"nextId" => $this->getNextId(),
				"thisId" => $recordId,
			]);
		}
		
		// if we didn't return within the if-block above, then we want
		// to report a failure to update.  we want to be sure to send back
		// the information necessary to re-display our form along with the
		// posted data, too.  then, a quick pass through our transformer,
		// just in case it's necessary we do so, and we're done here.
		
		return $this->payloadFactory->newUpdatePayload(false, [
			"errors" => $this->validator->getValidationErrors(),
			"schema" => $validationData["schema"],
			"posted" => $validationData["posted"],
		]);
	}
	
	protected function saveRecord(string $table, PayloadInterface $payload, array $key) {
		
		// our $payload contains the transformed record.  we can extract
		// that and and save our record in the specified table.
		
		$transformedRecord = $payload->getDatum("record");
		$this->db->update($table, $transformedRecord, $key);
	}
	
	
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 */
	public function delete(array $data): PayloadInterface {
		
		// to delete, we must have received a record ID and that ID must
		// live in the database.  our validator can handle this check for
		// us, but we'll need to send it the list of book IDs.
		
		$records = $this->getRecords();
		$validationData = array_merge($data, ["records" => $records]);
		if ($this->validator->validateDelete($validationData)) {
			
			// if the validator says we're okay, then actually deleting is
			// pretty straight forward:  we set the deleted flag for the
			// specified book and that'll hide it from the application.  the
			// book remains in the database, which is handy so that the next
			// time we parse Chummer data, it doesn't show up in the app
			// again.
			
			$values = ["deleted" => 1];
			$key = [$data["idName"] => $data["recordId"]];
			$this->db->update($data["table"], $values, $key);
			return $this->payloadFactory->newDeletePayload(true);
		}
		
		return $this->payloadFactory->newDeletePayload(false, [
			"errors" => $this->validator->getValidationErrors(),
		]);
	}
}
