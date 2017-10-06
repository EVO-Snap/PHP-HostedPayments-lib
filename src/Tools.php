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

namespace EvoSnap\HostedPayments;

use EvoSnap\HostedPayments\Exception\ErrorResponseException;

class Tools
{
    private static $ISO_LANGS = [
        'es' => 'SPA',
        'fr' => 'FRA',
        'pt' => 'PRT',
    ];

    /**
     * Calls EVO Snap* Hosted Payments Service.
     *
     * This function utilizes CURL to call the EVO Snap* service, and returns the raw response.
     *
     * @param array  $params    the parameters for the API call
     * @param string $url       the URL to call
     * @param bool   $verifySsl flag to determine if the SSL cert must be validated (Default to true)
     *
     * @return array JSON decoded results from the API call
     */
    public static function callEvoSnap($params, $url, $verifySsl = true)
    {
        $fparams = self::filterArray($params);
        $rCurl = curl_init();
        curl_setopt($rCurl, CURLOPT_URL, $url);
        curl_setopt($rCurl, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rCurl, CURLOPT_POST, true);
        curl_setopt($rCurl, CURLOPT_POSTFIELDS, self::encodeRequest($fparams));

        $result = ( array )json_decode(curl_exec($rCurl));

        curl_close($rCurl);

        return $result;
    }

    /**
     * Calculates a MAC signature.
     *
     * The MAC consists of all parameters for the API call in question sorted in alphabetical order by parameter name,
     * along with the merchant's private key.
     *
     * @see http://docs.evosnap.com/hosted-payments-section/message-authentication-code-mac/
     *
     * @param array  $aParams     parameters for the API call
     * @param string $sPrivateKey merchant's private key
     *
     * @return string the calculated MAC signature
     */
    public static function getMac($aParams, $sPrivateKey)
    {
        $sUnencoded = '';
        if (!empty($aParams)) {
            $aTemp = [];
            foreach ($aParams as $key => $val) {
                $aTemp[$key] = $val;
            }
            ksort($aTemp);
            $sUnencoded .= implode('', $aTemp);
        }
        if (!empty($sPrivateKey)) {
            $sUnencoded .= $sPrivateKey;
        }

        return md5($sUnencoded);
    }

    /**
     * URL encodes request data, including support for multi-dimensional arrays.
     *
     * @param array $aRequest the request to encode
     *
     * @return string the encoded request
     */
    public static function encodeRequest($aRequest)
    {
        $aFields = [];
        if (is_array($aRequest) && !empty($aRequest)) {
            foreach ($aRequest as $key => $val) {
                if (is_array($val) || is_object($val)) {
                    $aFields[] = $key . '=' . urlencode(json_encode($val));
                } else {
                    $aFields[] = $key . '=' . urlencode($val);
                }
            }
        }

        return implode('&', $aFields);
    }

    /**
     * Remove all values from the array that contain boolean false or blank values.
     *
     * @param array $a the array to filter
     *
     * @return array the resulting array
     */
    public static function filterArray($a)
    {
        foreach ($a as &$value) {
            if (is_array($value)) {
                $value = self::filterArray($value);
            }
        }

        return array_filter($a, 'self::valueTest');
    }

    /**
     * Normalize value comparisons for use in self::filterArray:
     * - For Strings, True if it contains a value, false if it's exactly equivalent with ''
     * - For Arrays, True if the array contains any values, false if the array is empty
     * - For Booleans, Exact Truth/False as the value, or False if null.
     *
     * @param mixed $value The value to test
     *
     * @return bool value calculated using the rules above
     */
    public static function valueTest($value)
    {
        if (is_string($value)) {
            $result = '' !== trim($value);
        } else {
            if (is_array($value)) {
                $result = !empty($value);
            } else {
                $result = !(is_null($value) || (false === $value));
            }
        }

        return $result;
    }

    /**
     * Trim and limit the length of a string for use with the EVO Snap* API.
     *
     * @param string $s the string to format
     * @param int    $l the length of the string (Default = 20)
     *
     * @return string
     */
    public static function getString($s, $l = 20)
    {
        return substr(trim($s), 0, $l);
    }

    /**
     * Format a number with two decimal places and commas as the thousands separator for EVO Snap*'s API.
     *
     * @param float $n the number to format
     *
     * @return string
     */
    public static function getNumber($n)
    {
        return number_format($n, 2);
    }

    /**
     * Gets a valid Boolean to send to EVO Snap*.
     *
     * @param bool $b the boolean to format
     *
     * @return string
     */
    public static function getBoolean($b)
    {
        return $b ? '1' : '0';
    }

    /**
     * Formats a ZIP code in a valid form for EVO Snap*.
     *
     * @param string $zip the ZIP code to format
     *
     * @return string zip Code in a valid format
     */
    public static function getZipCode($zip)
    {
        return self::getString(strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', $zip)), 9);
    }

    /**
     * Convert an Address model to an array formatted for use in the Snap* API.
     *
     * @param string                                $prefix  prefix for the form variable
     * @param \EvoSnap\HostedPayments\Model\Address $address address to format
     *
     * @return array the address formatted for use with the API
     */
    public static function getAddress($prefix, $address)
    {
        $aAddress = [];

        if ($address) {
            $aAddress[$prefix . '_company'] = self::getString($address->company);
            $aAddress[$prefix . '_first_name'] = self::getString($address->first_name);
            $aAddress[$prefix . '_last_name'] = self::getString($address->last_name);
            $aAddress[$prefix . '_po_box_number'] = self::getString($address->po_box_number);
            $aAddress[$prefix . '_address1'] = self::getString($address->address1);
            $aAddress[$prefix . '_address2'] = self::getString($address->address2);
            $aAddress[$prefix . '_house_number'] = self::getString($address->house_number);
            $aAddress[$prefix . '_city'] = self::getString($address->city);
            $aAddress[$prefix . '_zipcode'] = self::getZipCode($address->zipcode);
            $aAddress[$prefix . '_country'] = $address->country;
            $aAddress[$prefix . '_state'] = $address->state;
        }

        return $aAddress;
    }

    /**
     * Gets the language code used by Snap* from ISO value.
     *
     * @param string $iso_language the language ID
     *
     * @return string EVO Snap* language code
     */
    public static function getLanguage($iso_language)
    {
        $result = 'ENG'; // Default English

        if (isset($iso_language) &&
            array_key_exists($iso_language, self::$ISO_LANGS)) {
            $result = self::$ISO_LANGS[$iso_language];
        }

        return $result;
    }

    /**
     * Safely retrieve a value from the request, returning <code>null</code> if a parameter doesn't exist.
     *
     * @param array  $request the HTTP request
     * @param string $param   the name of the parameter you wish to extract
     *
     * @return mixed <NULL, string>
     */
    public static function getParam($request, $param)
    {
        if (isset($request[$param])) {
            $result = $request[$param];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Process the EVO Snap* API response, detect error conditions, and throw the appropriate exception when required.
     *
     * @param mixed $result EVO Snap* raw API response
     *
     * @throws ErrorResponseException
     *
     * @return bool <code>true</code> if the API call was a success
     */
    public static function checkResult($result)
    {
        if (is_array($result) && array_key_exists('success', $result)) {
            if (false != $result['success']) {
                return true;
            }

            throw new ErrorResponseException($result['message']);
        }

        throw new ErrorResponseException('An error has occurred during communication with EVO Snap*.');
    }
}
