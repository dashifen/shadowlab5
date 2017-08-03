<?php

namespace Shadowlab\Framework\AddOns\FormBuilders;

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
			"action"    => str_replace("update", "patch", $payload["currentUrl"]),
			"classes"   => isset($payload["title"]) ? $this->sanitize($payload["title"]) : "",
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
	 * @return void
	 * @throws FormBuilderException
	 */
	protected function confirmPayloadValidity(array $payload): void {
		
		// when working with our data, the $payload has to provide us with
		// the information we need to understand the schema we're working with.
		// specifically, it must have a schema and values index.  and, the
		// values index must refer to a third index where we find our data.
		
		$keys = array_keys($payload);
		if (!in_array("schema", $keys)) {
			throw new FormBuilderException("Unknown schema", FormBuilderException::UNKNOWN_SCHEMA);
		}
		
		if (!in_array("values", $keys)) {
			throw new FormBuilderException("Unknown values", FormBuilderException::UNKNOWN_VALUES);
		}
		
		$values = $payload["values"];
		if (!isset($payload[$values]) || !is_array($payload[$values])) {
			throw new FormBuilderException("Undefined values", FormBuilderException::UNDEFINED_VALUES);
		}
		
		// finally, even if we have all of that, if we don't know where we
		// are, then we cannot know to where our data is supposed to be sent.
		// so, we'd better have the current URL, too.
		
		if (!in_array("currentUrl", $keys)) {
			throw new FormBuilderException("Unknown URL", FormBuilderException::UNKNOWN_URL);
		}
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
		$this->confirmPayloadValidity($payload);
		
		// we have a currentFieldset property that defaults to -1.  that's so
		// we can increment it here and start our record of fieldsets at zero.
		// our Shadowlab forms are not expected to have more than one Fieldset
		// in them, but we'll leave the door open to that functionality later.
		
		$values = $payload[$payload["values"]];
		$legend = $this->findLegend($values);
		$fieldsetId = $this->sanitize($legend);
		$fieldsetLabel = sprintf('<label for="%s">%s</label>', $fieldsetId, $legend);
		
		$this->form["fieldsets"][++$this->currentFieldset] = [
			"id"     => $fieldsetId,
			"legend" => $fieldsetLabel,
			"fields" => [],
		];
		
		// and now. we want to loop over the schema in our $payload and add
		// fields.  the default implementation of this interface allows the
		// programmer to iteratively do this from outside the object, but we
		// want a more simple process to be handled in this applications
		// Actions.
		
		foreach ($payload["schema"] as $column => $columnData) {
			
			// the way our Domains send us table data is a map of column
			// names to column data.  we've constructed our addField()
			// method below to receive all of that at once, not as a map.
			// so, we'll cram our $column into $columnData and send it
			// to addField.  we'll also want to send the value for this
			// column, if available.  the other indices within $columnData
			// are in all caps, so we'll continue that trend.
			
			$columnData["COLUMN_NAME"] = $column;
			$columnData["COLUMN_VALUE"] = $values[$column] ?? $columnData["COLUMN_DEFAULT"];
			$this->addField($columnData);
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
				
				return $values[$key];
			}
		}
		
		// if we never found the key we were looking for, that's an unknown
		// legend.  that's a paddling.
		
		throw new FormBuilderException("Unknown legend", FormBuilderException::MISSING_LEGEND);
	}
	
	/**
	 * @param array  $payload
	 * @param string $object
	 *
	 * @return void
	 * @throws FormBuilderException
	 */
	public function addField(array $payload = [], string $object = 'Dashifen\Form\Fields\AbstractField'): void {
	
	
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
