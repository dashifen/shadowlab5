<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory\PoolBuilderFactory;
use Shadowlab\Framework\AddOns\Searchbar\SearchbarInterface;
use Dashifen\Domain\Payload\PayloadInterface;
use Aura\Di\Exception\ServiceNotFound;
use Dashifen\Domain\MysqlDomainException;
use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderFactory\PoolBuilderFactoryException;

class MatrixActionsAction extends AbstractAction {
	/**
	 * @param SearchbarInterface $searchbar
	 * @param PayloadInterface   $payload
	 *
	 * @return SearchbarInterface
	 */
	protected function getSearchbarFields(SearchbarInterface $searchbar, PayloadInterface $payload): SearchbarInterface {
		$searchbar->addSearch("Matrix Action", "matrix-action");
		$searchbar->addReset();
		return $searchbar;
	}


	/**
	 * @param array|null $post
	 *
	 * @return ResponseInterface
	 * @throws MysqlDomainException
	 * @throws PoolBuilderFactoryException
	 * @throws ServiceNotFound
	 */
	protected function createNewRecord(array $post = null): ResponseInterface {

		// when creating new matrix actions, we have to handle some
		// pool related information.  we'll separate out that work below,
		// and then handle the rest here.  then, we pass it back up to our
		// parent's version of the plugin where it's handled by our domain
		// and a response is produced for the visitor.

		$post = $this->request->getPost();
		$post = $this->handlePools($post);
		return parent::createNewRecord($post);
	}

	/**
	 * @param array $post
	 *
	 * @return array
	 * @throws ServiceNotFound
	 * @throws PoolBuilderFactoryException
	 */
	protected function handlePools(array $post): array {
		/** @var PoolBuilderFactory $factory */

		// here we take our $post information, pass it through our offensive
		// and defensive pool builders, and then return the results.  our pool
		// builders will check the database and handle the creation or
		// selection of the right pool ID for these data.

		$factory = $this->container->get("PoolBuilderFactory");
		$offensivePoolBuilder = $factory->getOffensiveAttrSkillPoolBuilder();
		$defensivePoolBuilder = $factory->getDefensiveAttrOnlyPoolBuilder();
		$post["offensive_pool_id"] = $offensivePoolBuilder->getPoolId($post);
		$post["defensive_pool_id"] = $defensivePoolBuilder->getPoolId($post);
		return $post;
	}

	/**
	 * @return string
	 */
	protected function getSingular(): string {
		return "matrix action";
	}
	
	/**
	 * @return string
	 */
	protected function getPlural(): string {
		return "matrix actions";
	}
	
	/**
	 * @return string
	 */
	protected function getTable(): string {
		return "matrix_actions";
	}
	
	/**
	 * @return string
	 */
	protected function getRecordIdName(): string {
		return "matrix_action_id";
	}
	
}
