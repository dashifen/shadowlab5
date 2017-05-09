<?php

namespace Shadowlab\CheatSheets\Other\Books;

use Dashifen\Response\ResponseInterface;
use Dashifen\Searchbar\SearchbarInterface;
use Interop\Container\ContainerInterface;
use Shadowlab\Framework\Action\AbstractAction;

class BooksAction extends AbstractAction {
	public function execute(string $parameter = "", ContainerInterface $container = null): ResponseInterface {
		
		// if our $parameter is not empty, then we're loading a specific
		// book rather than all of the books.  luckily, the domain will
		// know how to limit the SQL statement it uses based on the state
		// of what we send it.  we expect the parameter to be a book's
		// abbreviation (e.g. SR5) so that's how we name it for our domain.
		// note: the abbreviations in the database are in all caps, so
		// we'll be sure to send that information here.
		
		$payload = $this->domain->read(["abbr" => strtoupper($parameter)]);
		
		if ($payload->getSuccess()) {
			$books = $payload->getDatum("books");
			$searchbar = "";
			
			if (empty($parameter)) {
				
				// if we were selecting multiple books, then we need to make
				// the collection view's searchbar.  we can do so as follows,
				// utilizing that object's parse method.
				
				/** @var \Aura\Di\Container $container */
				/** @var SearchbarInterface $searchbar */
				
				$searchbar = $container->newInstance('Shadowlab\Framework\AddOns\Searchbar');
				$searchbar = $searchbar->parse($books);
			}
			
			$this->handleSuccess([
				"table"     => $books,
				"searchbar" => $searchbar,
				"title"     => $payload->getDatum("title"),
				"count"     => $payload->getDatum("count"),
				"caption"   => "The following are the SR books in this application.
					They're not all the published books for SR5; only those with
					quantifiable rules and stats are here.  Some, mostly the German
					content, are not included, i.e. you won't find their data
					elsewhere in the Shadowlab."
			]);
		} else {
			$this->handleFailure([
				"noun"  => empty($parameter) ? "books" : "book",
				"title" => "Perception Failed",
			]);
		}
		
		return $this->response;
	}
}
