<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Exceptions;

/**
 * An outbound request was refused because its destination is not one the deployment permits.
 *
 * Distinct from a transport failure on purpose: a caller that turns fetch errors into a generic "could not
 * reach the endpoint" response can catch this first and report that the destination itself was rejected,
 * which is a configuration or registration problem rather than a network one.
 *
 * Extends HttpException so that existing handling of fetch failures keeps working unchanged.
 */
class DestinationPolicyException extends HttpException
{
}
