<?php

namespace Shadowlab\Framework\Domain;

use Dashifen\Domain\AbstractMysqlDomain;
use Dashifen\Domain\Payload\PayloadInterface;
use Dashifen\Domain\DomainException;

/**
 * Class Domain
 *
 * This default Domain implementation for the Shadowlab simply throws a
 * giant tantrum all of the place.  Floor kicking.  Screaming.  All the
 * good stuff.  Children can overwrite these methods to help calm things
 * down.
 *
 * @package Shadowlab\Framework\Domain
 */
class Domain extends AbstractMysqlDomain {
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function create(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Create",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function read(array $data = []): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Read",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function update(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Update",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
	/**
	 * @param array $data
	 *
	 * @return PayloadInterface
	 * @throws DomainException
	 */
	public function delete(array $data): PayloadInterface {
		throw new DomainException("Unexpected Domain Behavior: Delete",
			DomainException::UNEXPECTED_BEHAVIOR);
	}
	
}
