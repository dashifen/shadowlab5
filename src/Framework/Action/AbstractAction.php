<?php

namespace Shadowlab\Framework\Action;

use Dashifen\Action\AbstractAction as DashifenAbstractAction;

/**
 * Class Action
 *
 * @package Shadowlab\Action
 */
abstract class AbstractAction extends DashifenAbstractAction {
	/**
	 * @var string $action
	 */
	protected $action = "read";
	
	/**
	 * @var int $recordId
	 */
	protected $recordId = 0;
	
	/**
	 * @param array $parameter
	 */
	protected function processParameter(array $parameter = []) {
		if (sizeof($parameter) > 0) {
			
			// if we have information to process, then we want to
			// remove the empty values.  we're then left with our
			// action and record ID number.
			
			$parameter = array_filter($parameter);
			$this->setAction(array_shift($parameter));
			
			if (sizeof($parameter) > 0) {
				$this->setRecordId(array_shift($parameter));
			}
		}
	}
	
	/**
	 * @param string $action
	 *
	 * @return void
	 * @throws ActionException
	 */
	protected function setAction(string $action): void {
		
		// in many apps, the idea of an update and a patch action would
		// seem redundant.  but, the update action is the one used when
		// we're getting information about a record and a patch is used
		// when we're sending information back to be saved in the
		// database.
		
		if (in_array($action, ["create", "read", "update", "patch", "delete"])) {
			$this->action = $action;
			return;
		}
		
		throw new ActionException("Unknown action: $action.");
	}
	
	/**
	 * @param int $recordId
	 *
	 * @return void
	 */
	protected function setRecordId(int $recordId) {
		$this->recordId = $recordId;
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleSuccess(array $data = []): void {
		
		// each of our handle* methods simply passes control down to the
		// respond method below.  that's the one that does our work, these
		// just tell it how to respond by passing the name of each of our
		// functions to it.  we use __FUNCTION__ instead of __METHOD__
		// because the latter includes the class name and we don't want
		// that.
		
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param string $function
	 * @param array  $data
	 *
	 * @return void
	 */
	protected function respond(string $function, array $data): void {
		
		// the purpose of this method -- indeed this entire object -- is
		// simply to ensure that we never forget to tell our response whether
		// or not we're authentic.  we can ask that object what's up and then
		// add it to our data.
		
		$authentic = $this->request->getSessionObj()->isAuthenticated() ? 1 : 0;
		$data = array_merge($data, ["authentic" => $authentic]);
		
		// we very specifically named our methods above so that they matched
		// the public methods of our response object.  that way we can use
		// a variable function call as follows to pass our newly authenticated
		// data over to it.  notice we pass our action property over to the
		// response; it's optional, but sometimes they need it.
		
		$this->response->{$function}($data, $this->action);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleFailure(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleError(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function handleNotFound(array $data = []): void {
		$this->respond(__FUNCTION__, $data);
	}
	
	/*
	 * the following methods are all used by children of this class when
	 * building forms.  form building is a part of our action because it
	 * relies on data from the domain and creates structures that are
	 * passed to the response to be sent to the client.
	 */
	
	protected function getFieldName(string $fieldId): string {
		
		// a fields name is related to its ID.  the database uses underscores
		// to separate words in a field's ID, so here we switch them to spaces
		// and then capitalize things.
		
		return ucwords(str_replace("_", " ", $fieldId));
	}
	
	protected function getFieldType(array $fieldData): string {
		$type = "Text";
		
		// within our $fieldData array is a DATA_TYPE index.  that data type
		// will tell us what sort of field this should be.
		
		switch ($fieldData["DATA_TYPE"]) {
			
			case "int":
			case "smallint":
			case "tinyint":
			case "bigint":
				
				// most of the time, our int fields simply require a
				// Number field so we can enter the appropriate number.
				// but, if there's also an OPTIONS array with data in
				// it, then we want a SelectOne.
				
				$type = sizeof($fieldData["OPTIONS"] ?? []) > 0
					? "SelectOne"
					: "Number";
				
				break;
				
			case "char":
			case "varchar":
				
				// usually, a Text field is good enough.  the trick is
				// to see if the CHARACTER_MAXIMUM_LENGTH field is greater
				// than 255.  if so, we'll switch to a text area.
				
				$type = ($fieldData["CHARACTER_MAXIMUM_LENGTH"] ?? 0) > 255
					? "TextArea"
					: "Text";
				
				break;
				
			case "text":
				$type = "TextArea";
				break;
				
			case "set":
			case "enum":
				$type = "SelectOne";
				break;
		}
		
		return $type;
	}
	
	/**
	 * @param array $fieldData
	 *
	 * @return bool
	 */
	protected function getFieldRequired(array $fieldData): bool {
		
		// the IS_NULLABLE field tells us whether or not it's required.
		// if the field cannot be null, then it is required.  testing this
		// is as easy as ...
		
		return $fieldData["IS_NULLABLE"] === "NO";
	}
	
	/**
	 * @param array $fieldData
	 *
	 * @return array
	 */
	protected function getFieldOptions(array $fieldData): array {
		
		// not all fields require options.  those that don't won't be
		// effected by having them, and those that do fail to work if we
		// don't have them.  luckily, our Domain tells us what the options
		// should be after getting them out of the database.  so, we can
		// just return them here.
		
		return $fieldData["OPTIONS"];
	}
	
}
