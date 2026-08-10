<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * How strictly the outbound destination policy insists on connecting to the address it validated.
 *
 * Pinning is what stops a hostname from resolving to a permitted address for the check and to an internal one
 * for the connection moments later. It needs the cURL handler, so it can not always be had.
 */
enum AddressPinningModeEnum: string
{
    /**
     * Pin where the handler allows it, and otherwise carry on with validation alone, reporting the weaker
     * guarantee to the logger. The default: a deployment without the cURL extension keeps working, and the
     * validation still refuses every destination that resolves inward at the time of the check.
     */
    case Preferred = 'preferred';

    /**
     * Refuse the request outright when the address can not be pinned. For deployments that can guarantee the
     * cURL handler and want the rebinding window closed rather than reported.
     */
    case Required = 'required';

    /**
     * Never pin. For a deployment that has taken the matter over itself (an egress proxy, a resolver that can
     * not return internal addresses) and does not want the reporting that Preferred produces.
     */
    case Disabled = 'disabled';
}
