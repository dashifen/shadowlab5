<?php

namespace Shadowlab\CheatSheets\Magic\Spells;

use Shadowlab\Framework\Response\ShadowlabResponse;

class SpellsResponse extends ShadowlabResponse {
	public function handleSuccess(array $data = [], string $action = "read"): void {
		$content = $data["count"] > 1 ? "collection.html" : "single.html";
		$this->setContent($content);
		$this->setData($data);
	}
	
	public function handleFailure(array $data = [], string $action = "read"): void {
		$this->setContent("not-found/record.php");
		$this->setData($data);
	}
}
