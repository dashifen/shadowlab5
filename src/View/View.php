<?php

namespace Shadowlab\View;

use Dashifen\Response\View\View as BaseView;
use Dashifen\Response\View\ViewInterface;

class View extends BaseView {
	public function getPrerequisites(string $pattern = ViewInterface::pattern): array {
		
		// since we're not using the default, server-side solution for
		// templating, we need to alter the way that our view knows about
		// which data it expects.  we can do that by passing a different
		// pattern to our parent's function to match vue's mustache markup.
		
		return parent::getPrerequisites("/{{ ?(\w+) ?}}/");
	}
	
	
	public function setDatum(string $index, $datum): void {
		parent::setDatum($index, $datum);
		
		// now that we've let our parent set things up for this index,
		// we might have something special to do if we're setting our
		// title.
		
		if ($index === "title") {
			
			// if we're setting our title and we do not have a heading
			// at this time, we're going to set that index as well.  it can
			// always be overwritten later if necessary, but this ensures
			// that these match when we don't have something special to
			// put in our heading.
			
			if (!$this->has("heading")) {
				parent::setDatum("heading", $datum);
			}
		}
	}
	
	public function compile(array $data = []): string {
		
		// to compile our view and prepare it for the client side, we want
		// to take our data as it stands now and convert it to JSON.  then,
		// we reset our data and cram the JSON string into it.  the default
		// compiler will cram that JSON into or template where the JS we've
		// written will use it to complete our view.  note: to make our
		// copyright statement easier, we'll just add the current year here,
		// too.
		
		$this->setData($data);
		$vueData = json_encode($this->data);
		$this->data = ["vueData" => $vueData, "year" => date("Y")];
		return parent::compile();
	}
}
