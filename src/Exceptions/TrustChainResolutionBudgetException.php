<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Exceptions;

/**
 * Thrown when a single Trust Chain resolution exhausts its resolution-wide budget (maximum number of entity
 * statement fetches, or the wall clock deadline).
 *
 * Unlike other errors encountered while walking the federation, this one must abort the whole resolution instead
 * of only the current chain path, so it is caught and rethrown explicitly during the recursion.
 */
class TrustChainResolutionBudgetException extends TrustChainException
{
}
