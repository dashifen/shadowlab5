<?php

namespace Shadowlab\CheatSheets;

use Shadowlab\Framework\Domain\Transformer;
use Dashifen\Domain\Payload\PayloadInterface;

class CheatSheetsTransformer extends Transformer {
	public function transformRead(PayloadInterface $payload): PayloadInterface {
		$original = $payload->getDatum("sheets");
		$transformed = [];
		
		// when we start, links is simply a list of our links to display,
		// but we want to re-organize them for easier display on screen with
		// our vue template.  this means more deeply nesting our data so
		// that the JavaScript can understand it most easily.
		
		$links = null;
		$currentType = "";
		foreach ($original as $sheet) {
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
		
		$payload->setDatum("sheets", $transformed);
		return $payload;
	}
}
