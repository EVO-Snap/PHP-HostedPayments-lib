<?php
/**
 * Copyright (c) 2015-2017 EVO Payments International - All Rights Reserved.
 *
 * This software and documentation is subject to and made
 * available only pursuant to the terms of an executed license
 * agreement, and may be used only in accordance with the terms
 * of said agreement. This software may not, in whole or in part,
 * be copied, photocopied, reproduced, translated, or reduced to
 * any electronic medium or machine-readable form without
 * prior consent, in writing, from EVO Payments International, INC.
 *
 * Use, duplication or disclosure by the U.S. Government is subject
 * to restrictions set forth in an executed license agreement
 * and in subparagraph (c)(1) of the Commercial Computer
 * Software-Restricted Rights Clause at FAR 52.227-19; subparagraph
 * (c)(1)(ii) of the Rights in Technical Data and Computer Software
 * clause at DFARS 252.227-7013, subparagraph (d) of the Commercial
 * Computer Software--Licensing clause at NASA FAR supplement
 * 16-52.227-86; or their equivalent.
 *
 * Information in this software is subject to change without notice
 * and does not represent a commitment on the part of EVO Payments
 * International.
 *
 * Sample Code is for REFERENCE ONLY and is intended to be used for
 * educational purposes. It's the responsibility of the software
 * company to properly integrate into their solution code that best
 * meets their production needs.
 */

namespace EvoSnap\HostedPayments\Model;

use EvoSnap\HostedPayments\Constants;

/**
 * Hosted Payments configuration.
 *
 * @author Antonio Molinero <antonio.molinero@evopayments.com>
 * @copyright Copyright (c) 2015-2017 EVO Snap* (http://www.evosnap.com)
 * @license    EVO Payments International EULA
 */
class Config
{
    /**
     * Environment. True for Live, False for certification.
     *
     * @var bool
     */
    public $environment;

    /**
     * Hosted Payments Merchant Profile Code.
     *
     * @var string
     */
    public $code;

    /**
     * Hosted Payments Merchant Profile Key.
     *
     * @var string
     */
    public $key;

    /**
     * Retrieves the URL.
     *
     * @param bool $checkout flag to indicate if the URL is for the checkout system
     *
     * @return string
     */
    public function getUrl($checkout)
    {
        if ($checkout) {
            $result = $this->environment ?
                Constants::LIVE_CHECKOUT_URL :
                Constants::CERT_CHECKOUT_URL;
        } else {
            $result = $this->environment ?
                Constants::LIVE_TXN_URL :
                Constants::CERT_TXN_URL;
        }

        return $result;
    }
}
