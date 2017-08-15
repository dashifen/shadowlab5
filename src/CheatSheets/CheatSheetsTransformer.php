<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Domain\AbstractTransformer;

class CheatSheetsTransformer extends AbstractTransformer {
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformAll(array $records): array {
		
		// because the cheat sheets handler is different from our more
		// regular collections, we can homogenize how we handle our read
		// transformations.
		
		return $this->transform($records);
	}
	
	/**
	 * @param array $records
	 *
	 * @return array
	 */
	protected function transformOne(array $records): array {
		
		// because the cheat sheets handler is different from our more
		// regular collections, we can homogenize how we handle our read
		// transformations.
		
		return $this->transform($records);
	}
	
	protected function transform(array $records): array {
		
		// when we start, links is simply a list of our links to display,
		// but we want to re-organize them for easier display on screen
		// with our vue template.  this means more deeply nesting our data
		// so that the JavaScript can understand it most easily.
		
		$links = null;
		$currentType = "";
		$transformed = [];
		foreach ($records as $sheet) {
			list($type, $text, $href) = array_values($sheet);
			if ($type != $currentType) {
				
				// if we've encountered a new type of sheet in our list, we
				// want to add the ones we've been organizing into the list
				// that we'll return below.  but, the first iteration will
				// also trigger this if-block and, in that case, we do not
				// want to add data to $transformed.
				
				if (!empty($currentType)) {
					$transformed[] = ["type" => $currentType, "links" => $links];
				}
				
				$currentType = $type;
				$links = [];
			}
			
			$links[] = ["text" => $text, "href" => $href];
		}
		
		// the loop above ends before the last set of links are added
		// to transformed.  so, we'll handle that here.
		
		$transformed[] = ["type" => $currentType, "links" => $links];
		return $transformed;
	}
}

