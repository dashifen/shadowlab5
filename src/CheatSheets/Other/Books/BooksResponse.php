<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Shadowlab\Framework\Response\Response;

class BooksResponse extends Response {
	public function handleSuccess(array $data = []): void {
		$content = $data["count"] > 1 ? "collection.html" : "single.html";
		$this->setContent($content);
		$this->setData($data);
		
		
	}
	
	public function handleFailure(array $data = []): void {
		$this->setContent("not-found/record.php");
		$this->setData($data);
	}
}
