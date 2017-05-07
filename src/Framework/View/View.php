<?php

namespace Shadowlab\Framework\View;

use Dashifen\Response\View\View as BaseView;
use Dashifen\Response\View\ViewInterface;

class View extends BaseView {
	public function getPrerequisites(string $pattern = ViewInterface::pattern): array {
		
		// since we're not using the default, server-side solution for
		// templating, we need to alter the way that our view knows about
		// which data it expects.  this requires both a different pattern
		// for matching template variables but, as a result, a different
		// way to identify a single set of prerequisites.  first, the
		// pattern:
		
		$pattern = "/(?:{{ ?(\w+) ?}})|(?:v-[^\"]+\"(\w+)\")/";
		
		// in the above pattern, there are two non-matching groups
		// that contain the moustache template (e.g. {{ title }})
		// and the vue.js attribute syntax (v-*="title") where the
		// * is often bind, text, or html but in our pattern, we
		// match anything that's not a quotation mark.  within those
		// groups, there are matching groups for \w+ so we can get
		// the names of our variables.
		
		preg_match_all($pattern, $this->content, $matches);
		
		// our $matches array now has three indices:  the matched
		// strings, the names of variables in moustache templates,
		// and the names of variables in the vue binding syntax.
		// we'll want to merge the latter two, remove duplicates,
		// and filter out empty strings in order to identify our
		// prerequisites.  one requirement won't be identified:
		// the page <title> because it's outside the scope of our
		// Vue object.  we'll add it here; if it was already found
		// above, it'll get filtered out anyway.
		
		$prerequisites = array_merge($matches[1], $matches[2], ["title"]);
		return array_filter(array_unique($prerequisites));
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
		// written will use it to complete our view.  eventually, we'll do
		// this as a single-pager sort of app and we won't need to cram our
		// data into a new server request, but that day is not today.  note:
		// to make our copyright statement easier, we'll just add the current
		// year here, too.  it helps keep Vue from yelling at us about using
		// a <script> tag to write it on the client side.
		
		$this->setData($data);
		$vueData = json_encode($this->data);
		$this->data = ["vueData" => $vueData, "year" => date("Y")];
		return parent::compile();
	}
}
