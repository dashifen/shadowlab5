<?php

namespace Shadowlab\Framework\AddOns;

use Dashifen\Form\Builder\FormBuilderInterface;
use Dashifen\Form\FormInterface;

/**
 * Class FormBuilder
 *
 * The Shadowlab needs a different sort of form builder.  Specifically, it
 * needs one that understands the binary language of moisture vaporators ...
 * err ... the database schema.  We use the FormBuilderInterface as defined
 * by the my form library, but we handle things somewhat differently herein.
 *
 * @package Shadowlab\Framework\AddOns\FormBuilders
 */
class FormBuilder implements FormBuilderInterface {
	/**
	 * @var array
	 */
	protected $form = [];
	
	/**
	 * @var int
	 */
	protected $currentFieldset = -1;
	
	/**
	 * FormBuilder constructor.
	 *
	 * @param array  $payload
	 * @param string $object
	 */
	public function __construct(array $payload = [], string $object = 'Dashifen\Form\Form') {
		
		// the default implementation of the FormBuilderInterface things of 
		// things in the way of a form's description in the arrays it works
		// with.  this time, we receive Payload data and need to understand
		// it to build our form.
		
		if (sizeof($payload) > 0) {
			$this->openForm($payload, $object);
		}
	}
	
	/**
	 * @param array  $payload
	 * @param string $object
	 *
	 * @return void
	 */
	public function openForm(array $payload = [], string $object = 'Dashifen\Form\Form') {
		$this->confirmPayloadValidity($payload);
	
		// now that we're done with that, we've confirmed that we have the
		// necessary capabilities to begin to build our form.  otherwise,
		// we'd have thrown an Exception and been out of here already.  we
		// use a static ID for our form to help write CSS that'll target it.
		
		$this->form = [
			"id"        => "shadowlab-form",
			"action"    => $payload["currentUrl"],
			"classes"   => [isset($payload["title"]) ? $this->sanitize($payload["title"]) : ""],
			"fieldsets" => [],
		];
		
		// now, we'll open our Fieldset and cram the fields described in our
		// payload into it.  this is different from the parent library's
		// implementation of the FormBuilder object which waits for the
		// programmer's instruction to open a Fieldset, but in this case we
		// have what we need to continue with out those pesky humans.
		
		$this->openFieldset($payload);
	}
	
	/**
	 * @param array $payload
	 *
	 * @return string
	 * @throws FormBuilderException
	 */
	protected function confirmPayloadValidity(array $payload): string {
		
		// when working with our data, the payload tells us about the table
		// we're working with and the records that we're working with.  even
		// though we're only going to be building a form for a single record,
		// that index in our payload is plural to homogenize its name when
		// working with collections or individual records.
		
		$keys = array_keys($payload);
		if (!in_array("schema", $keys)) {
			throw new FormBuilderException("Unknown schema", FormBuilderException::UNKNOWN_SCHEMA);
		}
		
		// our information is either to be found at the posted or the records
		// index.  here we'll test them both to determine which we're working
		// on now.
		
		if (in_array("records", $keys)) {
			$valuesIndex = "records";
		} elseif (in_array("posted", $keys)) {
			$valuesIndex = "posted";
		} else {
			throw new FormBuilderException("Unknown records", FormBuilderException::UNKNOWN_VALUES);
		}
		
		if (!is_array($payload[$valuesIndex])) {
			throw new FormBuilderException("Undefined values", FormBuilderException::UNDEFINED_VALUES);
		}
		
		// finally, even if we have all of that, if we don't know where we
		// are, then we cannot know to where our data is supposed to be sent.
		// so, we'd better have the current URL, too.
		
		if (!in_array("currentUrl", $keys)) {
			throw new FormBuilderException("Unknown URL", FormBuilderException::UNKNOWN_URL);
		}
		
		return $valuesIndex;
	}
	
	/**
	 * @param string $unsanitary
	 *
	 * @return string
	 */
	protected function sanitize(string $unsanitary): string {
		return strtolower(preg_replace("/\W+/", "-", $unsanitary));
	}
	
	/**
	 * @param array  $payload
	 * @param string $object
	 *
	 * @return void
	 */
	public function openFieldset(array $payload = [], string $object = 'Dashifen\Form\Fieldset\Fieldset'): void {
		$valuesIndex = $this->confirmPayloadValidity($payload);
		
		// we have a currentFieldset property that defaults to -1.  that's so
		// we can increment it here and start our record of fieldsets at zero.
		// our Shadowlab forms are not expected to have more than one Fieldset
		// in them, but we'll leave the door open to that functionality later.

		$values = $payload[$valuesIndex];
		$legend = $this->findLegend($values);
		$fieldsetId = $this->sanitize($legend);
		$this->form["fieldsets"][++$this->currentFieldset] = [
			"id"     => $fieldsetId,
			"legend" => $legend,
			"fields" => [],
		];
		
		// and now. we want to loop over the schema in our $payload and add
		// fields.  the default implementation of this interface allows the
		// programmer to iteratively do this from outside the object, but we
		// want a more simple process to be handled in this applications
		// Actions.
		
		foreach ($payload["schema"] as $column => $columnData) {
			
			// there are two columns that we do not want to be a part of
			// our forms:  the guid from chummer and the deleted flag.  if
			// we mess with the former, then future parses get funky, and
			// we have delete processes to handle the latter.
			
			if ($column !== "guid" && $column !== "deleted") {
				
				// the way our Domains send us table data is a map of column
				// names to column data.  we've constructed our addField()
				// method below to receive all of that at once, not as a map.
				// so, we'll cram our $column into $columnData and send it
				// to addField.  we'll also want to send the value for this
				// column, if available.  the other indices within $columnData
				// are in all caps, so we'll continue that trend.
				
				$columnData["COLUMN_NAME"] = $column;
				$columnData["COLUMN_VALUE"] = $this->getValues($values, $column, $columnData);
				$columnData["COLUMN_ERROR"] = isset($payload["errors"][$column])
					? $payload["errors"][$column]
					: false;
				
				$this->addField($columnData);
			}
		}
	}
	
	/**
	 * @param array $values
	 *
	 * @return string
	 * @throws FormBuilderException
	 */
	protected function findLegend(array $values): string {
		
		// within our $values is at least on database column that ends with
		// _id.  the first one of those defines the type of item we're working
		// on.  E.g., a book_id indicates we're working on a book.  so, we
		// look for the first key ending ing _id and then use it to determine
		// the key for our legend.
		
		$keys = array_keys($values);
		foreach ($keys as $key) {
			if (preg_match("/_id$/", $key)) {
				
				// if we've found a key that ends with _id, then we'll return
				// from within the loop.  this lets us short circuit it and
				// skip any additional _id fields that we might have
				// encountered.
				
				$otherKey = str_replace("_id", "", $key);
				return $values[$otherKey];
			}
		}
		
		// if we never found the key we were looking for, that's an unknown
		// legend.  that's a paddling.
		
		throw new FormBuilderException("Unknown legend",
			FormBuilderException::MISSING_LEGEND);
	}
	
	/**
	 * @param array  $values
	 * @param string $column
	 * @param array  $columnData
	 *
	 * @return string|array
	 */
	protected function getValues(array $values, string $column, array $columnData) {
		
		// identifying the value for a column isn't as straightforward as we
		// might like.  if we have a single, scalar value, then it's easy; the
		// hard part comes when we have a SelectMany field and need to work
		// with an array.
		
		$multiple = $columnData["MULTIPLE"] ?? false;
		
		if (!$multiple) {
			
			// for non multiple fields, we can return the value we find
			// in $values or the default in our $columnData.
			
			return $values[$column] ?? $columnData["COLUMN_DEFAULT"];
		}
		
		// if we haven't returned, then we need to prepare the array of values
		// for a SelectMany field.  in this case, there's a VALUES_KEY index
		// within our data which refers to the index within $values that has
		// our information in it.  that information is underscore-separated,
		// so we can explode it and then filter our blanks.
		
		$index = $columnData["VALUES_KEY"];
		$values = isset($values[$index]) ? explode("_", $values[$index]) : [];
		return array_filter($values);
	}
	
	/**
	 * @param array  $columnData
	 * @param string $object
	 *
	 * @return void
	 */
	public function addField(array $columnData = [], string $object = 'Dashifen\Form\Fields\AbstractField'): void {
		
		// to add a field, we want to analyze our $columnData to determine
		// the information about the field it describes.  to do this, we use
		// the protected functions that follow this one.
		
		$type = $this->getFieldType($columnData);
		
		$field = [
			"id"                   => $columnData["COLUMN_NAME"],
			"name"                 => $columnData["COLUMN_NAME"],
			"value"                => $columnData["COLUMN_VALUE"],
			"error"				   => $columnData["COLUMN_ERROR"],
			"label"                => $this->getFieldLabel($columnData["COLUMN_NAME"]),
			"additionalAttributes" => $this->getFieldAttributes($type, $columnData),
			"classes"              => $this->getFieldClasses($type, $columnData),
			"required"             => $this->getFieldRequired($columnData),
			"options"              => $this->getFieldOptions($columnData),
			"type"                 => $type,
		];
		
		$this->form["fieldsets"][$this->currentFieldset]["fields"][] = $field;
	}
	
	/**
	 * @param array $columnData
	 *
	 * @return string
	 */
	protected function getFieldType(array $columnData): string {
		$type = "Text";
		
		// within our $fieldData array is a DATA_TYPE index.  that data type
		// will tell us what sort of field this should be.
		
		switch ($columnData["DATA_TYPE"]) {
			
			case "int":
			case "smallint":
			case "tinyint":
			case "bigint":
			case "decimal":
				
				// most of the time, our int fields simply require a
				// Number field so we can enter the appropriate number.
				// but, if there's also an OPTIONS array with data in
				// it, then we want a SelectOne unless a MULTIPLE flag
				// is set; in which case we want a SelectMany
				
				$type = sizeof($columnData["OPTIONS"] ?? []) > 0
					? (isset($columnData["MULTIPLE"]) ? "SelectMany" : "SelectOne")
					: "Number";
				
				// if our type is Number, then we have one other exception:
				// the auto incrementing primary key for a table.  that should
				// be in a Hidden field instead.
				
				if ($type === "Number" && $columnData["EXTRA"] === "auto_increment") {
					$type = "Hidden";
				}
				
				break;
			
			case "char":
			case "varchar":
				
				// usually, a Text field is good enough.  the trick is
				// to see if the CHARACTER_MAXIMUM_LENGTH field is greater
				// than 255.  if so, we'll switch to a text area.
				
				$type = ($columnData["CHARACTER_MAXIMUM_LENGTH"] ?? 0) > 255
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
	 * @param string $columnName
	 *
	 * @return string
	 */
	protected function getFieldLabel(string $columnName) {
		
		// our database uses underscores to separate words within column
		// names.  so, we'll replace those with spaces and then capitalize
		// the name so that we can return a more display-ready phrase.
		
		$name = ucwords(str_replace("_", " ", $columnName));
		return $this->transformFieldName($name);
	}
	
	/**
	 * @param string $name
	 *
	 * @return string
	 */
	protected function transformFieldName(string $name): string {
		
		// sometimes we use abbreviations within our column names that
		// we don't want to use on-screen.  so, we will transform some of
		// our names using this switch statement.
		
		switch ($name) {
			case "Abbr":
				$name = "Abbreviation";
				break;
			
			case "Page":
				$name = "Page Number";
				break;
		}
		
		// finally, if we have " Id" in the name, we just remove it.
		// there's no need for that on-screen.  rather than test for
		// it's existence and remove it only when necessary, we'll just
		// try to replace it; if it's not there, then nothing happens.
		
		return str_replace(" Id", "", $name);
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
	 * @param string $columnType
	 * @param array  $columnData
	 *
	 * @return array
	 */
	protected function getFieldAttributes(string $columnType, array $columnData): array {
		
		// a few of our column types require additional attributes.  we'll
		// identify those here and, when necessary grab information out of
		// the rest of our column data to assist.
		
		$attr = [];
		switch ($columnType) {
			case "Number":
				
				// for number fields, we want to try and set min and step
				// attributes.  step is easiest and is based on the
				// NUMERIC_SCALE; if it's zero, our step size is 1 (i.e.
				// whole numbers).  otherwise, it represents the number of
				// significant decimal figures.
				
				$attr["step"] = $columnData["NUMERIC_SCALE"] != 0
					? sprintf(".%0".$columnData["NUMERIC_SCALE"]."d", 1)
					: 1;
				
				// now, for our minimum value, if we can find the string
				// "unsigned" in our column type, then the minimum is zero.
				// otherwise, we don't set it at all.
				
				if (strpos($columnData["COLUMN_TYPE"], "unsigned")!==false) {
					$attr["min"] = 0;
				}
				
				break;
				
			case "Text":
				$maxLength = $columnData["CHARACTER_MAXIMUM_LENGTH"];
				if (is_numeric($maxLength) && $maxLength <= 255) {
					$attr = ["maxlength" => $maxLength];
				}
				
				break;
		}
		
		return $attr;
	}
	
	/**
	 * @param string $type
	 * @param array $columnData
	 *
	 * @return array
	 */
	protected function getFieldClasses(string $type, array $columnData): array {
		
		// most of the time, we can skip classes, but for Text, TextArea,
		// and Number fields, we do want to provide some guidance here.
		
		$classes = [];
		if ($type === "Number") {
			
			// Numbers are easy:  we just want them to be wide enough
			// for say five or six digits.  that's enough for our
			// purposes here.
			
			$classes[] = "w20";
		} elseif ($type === "Text" || $type === "TextArea") {
			
			// here we need to worry about both width and, for text areas,
			// height.
			
			$maxLength = $columnData["CHARACTER_MAXIMUM_LENGTH"];
			if (!is_numeric($maxLength)) {
				$maxLength = 0;
			}
			
			$classes[] = $this->getFieldWidth($type, $maxLength);
			
			if ($type === "TextArea") {
				$classes[] = $this->getFieldHeight($maxLength);
			}
		}
		
		return $classes;
	}
	
	/**
	 * @param string $type
	 * @param int    $maxLength
	 *
	 * @return string
	 */
	protected function getFieldWidth(string $type, int $maxLength): string {
		
		// for TextAreas, we just use 80% to give a little bit of a gutter
		// on the right, but for Text fields, we want the percentage to
		// reflect the maximum length a bit more closely.
		
		if ($type === "TextArea" || $maxLength === 0) {
			return "w80";
		}
		
		// if we're still here, then we do have a maximum length and,
		// therefore, we want our width to reflect it.  at minimum, we
		// want to a 15% width.  but we'll go up to 80% as we approach
		// a maxlength of 255.  then, we also return the nearest multiple
		// of 5 so that we match our atomic CSS width specifications.
		// to get than nearest multiple of five, we used this source:
		// https://stackoverflow.com/a/4133893/360838.
		
		$widthPercent = $maxLength / 255 * 80;
		$nearestFive = round(($widthPercent + 5/2)/5) * 5;
		
		if ($nearestFive < 15) {
			$nearestFive = 15;
		}
		
		if ($nearestFive > 80) {
			$nearestFive = 80;
		}
		
		return sprintf("w%d", $nearestFive);
	}
	
	/**
	 * @param int    $maxLength
	 *
	 * @return string
	 */
	protected function getFieldHeight(int $maxLength): string {
		
		// here we want to return one of our atomic height specifications:
		// small, medium, or large.  we base this off of our maximum length
		// as follows:
		
		$height = "large";
		if ($maxLength <= 255) {
			$height = "small";
		} elseif ($maxLength <= 65535) {
			$height = "medium";
		}
		
		return $height;
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
	
	/**
	 * @param string $object
	 *
	 * @return FormInterface
	 */
	public function getForm(string $object = 'Dashifen\Form\Form'): FormInterface {
		/** @var FormInterface $object */
		
		return $object::parse($this->getFormJson());
	}
	
	/**
	 * @return string
	 */
	public function getFormJson(): string {
		return json_encode($this->form);
	}
	
	/**
	 * @return string
	 * @deprecated 1.9.0 use getFormJson() instead.
	 */
	public function build(): string {
		return $this->getFormJson();
	}
	
}
