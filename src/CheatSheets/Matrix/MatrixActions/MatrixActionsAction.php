<?php

namespace Shadowlab\CheatSheets\Matrix\MatrixActions;

use Dashifen\Response\ResponseInterface;
use Shadowlab\Framework\Action\AbstractAction;
use Shadowlab\Framework\AddOns\PoolBuilder\PoolBuilderException;
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
	 * @throws PoolBuilderException
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
	 * @throws PoolBuilderException
	 */
	protected function handlePools(array $post): array {
		/** @var PoolBuilderFactory $factory */

		// here we take our $post information, pass it through our offensive
		// and defensive pool builders, and then return the results.  our pool
		// builders will check the database and handle the creation or
		// selection of the right pool ID for these data.

		$factory = $this->container->get("PoolBuilderFactory");
		$post = $this->handleOffensivePool($post, $factory);
		$post = $this->handleDefensivePool($post, $factory);
	}

	/**
	 * @param array              $post
	 * @param PoolBuilderFactory $factory
	 *
	 * @return array
	 * @throws PoolBuilderException
	 * @throws PoolBuilderFactoryException
	 */
	protected function handleOffensivePool(array $post, PoolBuilderFactory $factory): array {
		$offensivePoolBuilder = $factory->getOffensiveAttrSkillPoolBuilder();
		$post["offensive_pool_id"] = $offensivePoolBuilder->getPoolId($post);
		return $offensivePoolBuilder->removePoolConstituents($post);
	}

	/**
	 * @param array              $post
	 * @param PoolBuilderFactory $factory
	 *
	 * @return array
	 * @throws PoolBuilderException
	 * @throws PoolBuilderFactoryException
	 */
	protected function handleDefensivePool(array $post, PoolBuilderFactory $factory): array {
		$defensivePoolBuilder = $factory->getDefensiveAttrOnlyPoolBuilder();

		try {

			// for our defensive pool, sometimes we have attributes to use
			// (like INT + Firewall).  but, for the Grid Hop actions, there's
			// simple a static pool.  we'll try to use our pool builder, but
			// if it ends up throwing a non-numeric constituent exception, we
			// can fall back on the static pool information.

			$post["defensive_pool_id"] = $defensivePoolBuilder->getPoolId($post);
		} catch (PoolBuilderException $exception) {
			if ($exception->getCode() !== PoolBuilderException::NON_NUMERIC_CONSTITUENT) {

				// any other type of of pool builder exception other than our
				// non-numeric constituent exception we'll just re-throw.  that
				// probably means the page dies, but that's okay in this case.

				throw $exception;
			}

			// if we did have a non-numeric constituent exception, then we have
			// to have a numeric static defensive pool value in the posted
			// data.  if we don't, we throw a missing constituent pool builder
			// exception.

			if (!is_numeric(($post["static_defensive_pool"] ?? false))) {
				throw new PoolBuilderException("Static defensive pool missing.",
					PoolBuilderException::MISSING_CONSTITUENTS,
					$exception);
			}
		}

		// now, since either (a) we gleaned our defensive pool ID from the
		// posted data, or (b) it was missing and we had a static pool to use
		// instead, we want to remove the pool constituents from $post because
		// they're no longer needed.  we can do this here, instead of the try
		// block above, because we need to do it regardless of how we reached
		// this point.

		return $defensivePoolBuilder->removePoolConstituents($post);
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
