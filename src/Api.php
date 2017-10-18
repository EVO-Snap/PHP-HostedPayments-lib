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

/**
 * Class Api.
 *
 * Hosted Payments is a web-based payment and order storage platform that handles both individual orders as well as
 * subscriptions, while keeping PCI protected data outside the scope of your application.
 *
 * To use Hosted Payments, create an \EvoSnap\HostedPayments\Model\HostedPayments object, fill in the configuration values provided to
 * you as part of the Evo Snap* onboarding process, then use this API object to make the various API calls to the
 * Hosted Payments service.
 *
 * Calls usually require you to fill an \EvoSnap\HostedPayments\Model object with data from your application (online forms, etc), which
 * is then sent by an API method to the Hosted Payments service.  For more details, see our documentation.
 *
 * @see http://docs.evosnap.com/hosted-payments-section/hp-api-calls-parameters/
 *
 * @package EvoSnap\HostedPayments
 *
 * @version 1.1
 */
class Api
{
    /**
     * Retrieves a list of orders processed by Hosted Payments.
     *
     * @param array                                $params request parameters
     * @param \EvoSnap\HostedPayments\Model\Config $cfg    hosted payments configuration
     *
     * @return array a list of orders processed by HostedPayments
     */
    public static function getOrders($params, $cfg)
    {
        $aPost = self::getEvoSnapOrdersPost($params, $cfg->code, $cfg->key);
        $orders = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($orders)) {
            return $orders['orders'];
        }

        return [];
    }

    /**
     * Retrieves a specific order.
     *
     * @param string                               $id  the order ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return mixed the order
     */
    public static function getOrder($id, $cfg)
    {
        $aPost = self::getEvoSnapOrderPost($id, $cfg->code, $cfg->key);
        $order = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($order)) {
            $raw_order = $order['order'];
            self::setOrderTransactionData($raw_order);

            return $raw_order;
        }

        return false;
    }

    /**
     * Gets the status for a specific order.
     *
     * @param string                               $id  the order ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return string the order status
     */
    public static function getOrderStatus($id, $cfg)
    {
        $order = self::getOrder($id, $cfg);

        if (null != $order) {
            $result = $order->status;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Gets the checkout URL, which you will then either redirect to or display in an iframe, depending on how you
     * configured your checkout model.
     *
     * @param \EvoSnap\HostedPayments\Model\AbstractCheckout $order      the order checkout
     * @param float                                          $trigger3ds value that triggers 3D secure
     * @param \EvoSnap\HostedPayments\Model\Config           $cfg        hosted payments configuration
     *
     * @return string the checkout URL
     */
    public static function getCheckoutUrl($order, $trigger3ds, $cfg)
    {
        $aPost = self::getEvoSnapCheckoutPost($order, $trigger3ds, $cfg->code, $cfg->key);
        $result = Tools::callEvoSnap($aPost, $cfg->getUrl(true), $cfg->environment);
        if (Tools::checkResult($result)) {
            return $result ['url'];
        }

        return '';
    }

    /**
     * Apply a credit to a specific order.
     *
     * @param string                               $id     the order ID
     * @param string                               $txnId  the transaction ID
     * @param float                                $amount the amount to credit, NULL defaults to original transaction amount
     * @param \EvoSnap\HostedPayments\Model\Config $cfg    hosted payments configuration
     *
     * @return string the transaction ID
     */
    public static function creditOrder($id, $txnId, $amount, $cfg)
    {
        $aPost = self::getEvoSnapCreditOrderPost(
            $id,
            $txnId,
            $amount,
            $cfg->code,
            $cfg->key
        );
        $credit = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($credit)) {
            return $credit['txn_id'];
        }

        return '';
    }

    /**
     * Retrieves a list of merchant callbacks previously sent by Hosted Payments to your application.  Defaults to the
     * last 20 callbacks sent previous to the API call.
     *
     * @param array                                $params request parameters
     * @param \EvoSnap\HostedPayments\Model\Config $cfg    hosted payments configuration
     *
     * @return array the callback list
     */
    public static function getCallbacks($params, $cfg)
    {
        $aPost = self::getEvoSnapCallbacksPost($params, $cfg->code, $cfg->key);
        $orders = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($orders)) {
            return $orders['results'];
        }

        return [];
    }

    /**
     * Retrieves a specific subscription.
     *
     * @param string                               $id  the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return mixed the subscription
     */
    public static function getSubscription($id, $cfg)
    {
        $aPost = self::getEvoSnapSubscriptionPost($id, 'get_subscription', $cfg->code, $cfg->key);
        $subscription = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($subscription)) {
            return $subscription['subscription'];
        }

        return false;
    }

    /**
     * Gets the status of a specific subscription.
     *
     * @param string                               $id  the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return string the order status
     */
    public static function getSubscriptionStatus($id, $cfg)
    {
        $subscription = self::getSubscription($id, $cfg);

        if (null != $subscription) {
            $result = $subscription->status;
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Performs a subscription payment process against an active, manually processed subscription with a payment
     * due to process.
     *
     * @param string                               $id       the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg      hosted payments configuration
     * @param string                               $order_id (optional) order ID, will be auto-generated if omitted amount
     *
     * @return array the resulting 'merchant_order_id' and the 'txn_id' of the payment
     */
    public static function processSubscription($id, $cfg, $order_id = null)
    {
        $aPost = self::getEvoSnapSubscriptionPost(
            $id,
            'process_subscription',
            $cfg->code,
            $cfg->key
        );
        $aPost['merchant_order_id'] = $order_id;
        $payment = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($payment)) {
            return [
                'merchant_order_id' => $payment['merchant_order_id'],
                'txn_id'            => $payment['txn_id'],
            ];
        }

        return [];
    }

    /**
     * Suspend a specific subscription.
     *
     * @param string                               $id  the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return bool <code>true</code> if the action is successfully completed
     */
    public static function suspendSubscription($id, $cfg)
    {
        $aPost = self::getEvoSnapSubscriptionPost(
            $id,
            'suspend_subscription',
            $cfg->code,
            $cfg->key
        );
        $suspend = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);

        return Tools::checkResult($suspend);
    }

    /**
     * Resume a specific subscription.
     *
     * @param string                               $id  the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return bool <code>true</code> if the action is successfully completed
     */
    public static function resumeSubscription($id, $cfg)
    {
        $aPost = self::getEvoSnapSubscriptionPost(
            $id,
            'resume_subscription',
            $cfg->code,
            $cfg->key
        );
        $resume = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);

        return Tools::checkResult($resume);
    }

    /**
     * Cancel a specific subscription.
     *
     * @param string                               $id  the subscription ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return bool <code>true</code> if the action is successfully completed
     */
    public static function cancelSubscription($id, $cfg)
    {
        $aPost = self::getEvoSnapSubscriptionPost(
            $id,
            'cancel_subscription',
            $cfg->code,
            $cfg->key
        );
        $cancel = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);

        return Tools::checkResult($cancel);
    }

    /**
     * Start the checkout process to generate a payment token.  A token allows you to make future transactions without
     * going through the entire payment process.
     *
     * @param \EvoSnap\HostedPayments\Model\TokenCheckout $token    the token checkout
     * @param bool                                        $enable3d enable 3D secure
     * @param \EvoSnap\HostedPayments\Model\Config        $cfg      hosted payments configuration
     *
     * @return string the checkout URL
     */
    public static function getTokenCheckoutUrl($token, $enable3d, $cfg)
    {
        $aPost = self::getEvoSnapTokenCheckoutPost($token, $enable3d, $cfg->code, $cfg->key);
        $result = Tools::callEvoSnap($aPost, $cfg->getUrl(true), $cfg->environment);
        if (Tools::checkResult($result)) {
            return $result ['url'];
        }

        return '';
    }

    /**
     * Retrieve a list of all active stored tokens.
     *
     * @param array                                $params request parameters
     * @param \EvoSnap\HostedPayments\Model\Config $cfg    hosted payments configuration
     *
     * @return array the orders
     */
    public static function getTokens($params, $cfg)
    {
        $aPost = self::getEvoSnapTokensPost($params, $cfg->code, $cfg->key);
        $tokens = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($tokens)) {
            return $tokens['tokens'];
        }

        return [];
    }

    /**
     * Retrieve a specific token.
     *
     * @param string                               $id  the token ID
     * @param \EvoSnap\HostedPayments\Model\Config $cfg hosted payments configuration
     *
     * @return string the token
     */
    public static function getToken($id, $cfg)
    {
        $aPost = self::getEvoSnapTokenPost($id, $cfg->code, $cfg->key);
        $token = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($token)) {
            return $token['token'];
        }

        return '';
    }

    /**
     * Process an order utilizing stored payment details via a token.
     *
     * @param string                               $id       the token ID
     * @param \EvoSnap\HostedPayments\Model\Order  $order    the order
     * @param \EvoSnap\HostedPayments\Model\Config $cfg      hosted payments configuration
     * @param string                               $delegate (optional) parent or child merchant
     *                                                       code delegated to collect the processed payment
     *
     * @return array the token
     */
    public static function processTokenOrder($id, $order, $cfg, $delegate = null)
    {
        $aPost = self::getEvoSnapProcessTokenOrderPost($id, $order, $cfg->code, $cfg->key, $delegate);
        $token = Tools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
        if (Tools::checkResult($token)) {
            return $token;
        }

        return [];
    }

    // AND THAT'S IT!
    // All the methods below are internal methods to process details.
    // The entire Hosted Payments API is implemented in the methods above!

    /**
     * Compile the POST parameters used by Get Orders.
     *
     * @param $params array  Parameters pre-compiled by parent function
     * @param $code   string Merchant code provided by Snap*
     * @param $key    string Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapOrdersPost($params, $code, $key)
    {
        $aPost = [
            'action' => 'get_orders',
            'code'   => $code,
            'return' => 'json',
        ];
        $aPost['mac'] = self::getOrdersMac($aPost, $key);
        $aPost = array_merge($aPost, $params);

        return $aPost;
    }

    /**
     * Get the MAC for a list orders call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getOrdersMac($aPost, $key)
    {
        $aMACParams = [
            'code'   => $aPost['code'],
            'action' => $aPost['action'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Calculate the amount of a transaction which is a "credit.".
     *
     * @param \EvoSnap\HostedPayments\Model\Order $raw_order order specific values for API call
     */
    private static function setOrderTransactionData(&$raw_order)
    {
        $total_refunded = 0;
        if (isset($raw_order->transactions) && is_array($raw_order->transactions)) {
            foreach ($raw_order->transactions as $txn) {
                if ('sale' === $txn->txn_action) {
                    $raw_order->payment_transaction = $txn;
                } elseif ('credit' === $txn->txn_action) {
                    $total_refunded += $txn->txn_amount;
                }
            }
        }

        $raw_order->total_refunded = $total_refunded;
    }

    /**
     * Compile the POST parameters used by Get Order.
     *
     * @param $id   string The "Order ID" to be fetched
     * @param $code string Merchant code provided by Snap*
     * @param $key  string Secret key provided by Snap*
     *              array POST variables, including calculated MAC
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapOrderPost($id, $code, $key)
    {
        $aPost = [
            'action'            => 'get_order',
            'code'              => $code,
            'merchant_order_id' => $id,
            'return'            => 'json',
        ];
        $aPost['mac'] = self::getOrderMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the MAC for an order status call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getOrderMac($aPost, $key)
    {
        $aMACParams = [
            'code'              => $aPost['code'],
            'action'            => $aPost['action'],
            'merchant_order_id' => $aPost['merchant_order_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Get the POST variables necessary for the EvoSnap Checkout call.
     *
     * @param \EvoSnap\HostedPayments\Model\AbstractCheckout $checkout
     * @param float                                          $trigger3ds
     * @param string                                         $code
     * @param string                                         $key
     *
     * @return array
     */
    private static function getEvoSnapCheckoutPost($checkout, $trigger3ds, $code, $key)
    {
        $returnUrl = $checkout->return_url;
        $cancelUrl = $checkout->cancel_url;

        $customer = $checkout->customer;
        $aCustomer = [
            'merchant_customer_id' => $customer->id,
            'first_name'           => Tools::getString($customer->first_name),
            'last_name'            => Tools::getString($customer->last_name),
            'phone'                => Tools::getString($customer->phone, 15),
            'email'                => Tools::getString($customer->email, 50),
        ];

        $aPost = [
            'action'          => $checkout->getAction(),
            'code'            => $code,
            'customer'        => $aCustomer,
            'return_url'      => $returnUrl,
            'cancel_url'      => $cancelUrl,
            'auto_return'     => Tools::getBoolean($checkout->auto_return),
            'checkout_layout' => $checkout->checkout_layout,
            'language'        => $checkout->language,
            'create_token'    => Tools::getBoolean($checkout->create_token),
        ];

        if (property_exists($checkout, 'order') && !empty($checkout->order)) {
            $aPost = array_merge($aPost, self::getOrderPost($checkout->order, $trigger3ds));
        }
        if (property_exists($checkout, 'subscription') && !empty($checkout->subscription)) {
            $aPost = array_merge($aPost, self::getSubscriptionPost($checkout->subscription, $trigger3ds));
        }

        $aPost['mac'] = self::getCheckoutMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the POST variables necessary for the EvoSnap Order call.
     *
     * @param \EvoSnap\HostedPayments\Model\Order $order
     * @param float                               $trigger3ds
     *
     * @return array
     */
    private static function getOrderPost($order, $trigger3ds)
    {
        $aOrder = [
            'merchant_order_id' => Tools::getString($order->id, 255),
            'total_subtotal'    => Tools::getNumber($order->total_subtotal),
            'total_tax'         => Tools::getNumber($order->total_tax),
            'total_shipping'    => Tools::getNumber($order->total_shipping),
            'total_discount'    => Tools::getNumber($order->total_discount),
            'total'             => Tools::getNumber($order->total),
            'ship_method'       => Tools::getString($order->ship_method),
            'currency_code'     => Tools::getString($order->currency_code),
            'enable_3d'         => Tools::getBoolean(isset($trigger3ds) && ($order->total >= $trigger3ds)),
        ];

        $aOrderLines = [];
        $products = $order->lines;
        for ($i = 0; $i < count($products); $i++) {
            $aOrderLines[$i] = self::getOrderItem($products[$i]);
        }

        $aOrder = array_merge($aOrder, Tools::getAddress('billto', $order->billto_address));
        if (isset($order->shipto_address)) {
            $aOrder = array_merge($aOrder, Tools::getAddress('shipto', $order->shipto_address));
        }

        return ['order' => $aOrder, 'order_item' => $aOrderLines];
    }

    /**
     * Get the POST variables necessary for the EvoSnap Subscription call.
     *
     * @param \EvoSnap\HostedPayments\Model\Subscription $subscription
     * @param float                                      $trigger3ds
     *
     * @return array
     */
    private static function getSubscriptionPost($subscription, $trigger3ds)
    {
        $aSubscription = [
            'merchant_subscription_id' => Tools::getString($subscription->id, 255),
            'interval_length'          => $subscription->interval_length,
            'interval_unit'            => $subscription->interval_unit,
            'start_date'               => $subscription->start_date,
            'total_occurrences'        => $subscription->total_occurrences,
            'trial_occurrences'        => $subscription->trial_occurrences,
            'auto_process'             => $subscription->auto_process,
            'total_subtotal'           => Tools::getNumber($subscription->total_subtotal),
            'total_tax'                => Tools::getNumber($subscription->total_tax),
            'total_shipping'           => Tools::getNumber($subscription->total_shipping),
            'total_discount'           => Tools::getNumber($subscription->total_discount),
            'total'                    => Tools::getNumber($subscription->total),
            'trial_amount'             => Tools::getNumber($subscription->trial_amount),
            'ship_method'              => Tools::getString($subscription->ship_method),
            'currency_code'            => Tools::getString($subscription->currency_code),
            'enable_3d'                => Tools::getBoolean(isset($trigger3ds) && ($subscription->total >= $trigger3ds)),
        ];

        if ($subscription->trial_occurrences && ($subscription->trial_occurrences > 0)) {
            $aSubscription['trial_occurrences'] = $subscription->trial_occurrences;
            $aSubscription['trial_amount'] = $subscription->trial_amount;
        }

        $aSubscriptionLines = [];
        $products = $subscription->lines;
        for ($i = 0; $i < count($products); $i++) {
            $aSubscriptionLines[$i] = self::getOrderItem($products[$i]);
        }

        $aSubscription = array_merge($aSubscription, Tools::getAddress('billto', $subscription->billto_address));
        if (isset($subscription->shipto_address)) {
            $aSubscription = array_merge($aSubscription, Tools::getAddress('shipto', $subscription->shipto_address));
        }

        return ['sub' => $aSubscription, 'sub_item' => $aSubscriptionLines];
    }

    /**
     * Get the POST variables describing an order line item.
     *
     * @param \EvoSnap\HostedPayments\Model\LineItem $orderItem
     *
     * @return array
     */
    private static function getOrderItem($orderItem)
    {
        return [
            'sku'         => Tools::getString($orderItem->sku, 25),
            'name'        => Tools::getString($orderItem->name, 30),
            'description' => Tools::getString($orderItem->description, 300),
            'qty'         => Tools::getNumber($orderItem->qty),
            'price'       => Tools::getNumber($orderItem->price),
            'tax'         => Tools::getNumber($orderItem->tax),
        ];
    }

    /**
     * Get the MAC for a checkout call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getCheckoutMac($aPost, $key)
    {
        $aMACParams = [
            'code'  => $aPost['code'],
            'email' => $aPost['customer']['email'],
        ];

        if (array_key_exists('order', $aPost)) {
            $aOrderMACParams = [
                'order_total_subtotal' => $aPost['order']['total_subtotal'],
                'order_total'          => $aPost['order']['total'],
                'merchant_order_id'    => $aPost['order']['merchant_order_id'],
            ];

            $aMACParams = array_merge($aMACParams, $aOrderMACParams);
        }

        if (array_key_exists('sub', $aPost)) {
            $aSubMACParams = [
                'sub_total_subtotal'       => $aPost['sub']['total_subtotal'],
                'sub_total_occurrences'    => $aPost['sub']['total_occurrences'],
                'sub_total'                => $aPost['sub']['total'],
                'sub_trial_amount'         => $aPost['sub']['trial_amount'],
                'sub_trial_occurrences'    => $aPost['sub']['trial_occurrences'],
                'merchant_subscription_id' => $aPost['sub']['merchant_subscription_id'],
            ];

            $aMACParams = array_merge($aMACParams, $aSubMACParams);
        }

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by Credit call.
     *
     * @param string $id     order ID to do a Credit transaction against
     * @param string $txnId  The transaction to credit
     * @param float  $amount the amount to credit, NULL defaults to original transaction amount
     * @param string $code   Merchant code provided by Snap*
     * @param string $key    Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapCreditOrderPost($id, $txnId, $amount, $code, $key)
    {
        $aPost = [
            'action'            => 'credit',
            'code'              => $code,
            'merchant_order_id' => $id,
            'txn_id'            => $txnId,
            'return'            => 'json',
        ];
        $aPost['mac'] = self::getCreditOrderMac($aPost, $key);

        if (null != $amount) {
            $aPost['amount'] = $amount;
        }

        return $aPost;
    }

    /**
     * Get the MAC for a "Add Credit To Order" call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getCreditOrderMac($aPost, $key)
    {
        $aMACParams = [
            'code'              => $aPost['code'],
            'action'            => $aPost['action'],
            'merchant_order_id' => $aPost['merchant_order_id'],
            'txn_id'            => $aPost['txn_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by Get Callbacks call.
     *
     * @param array  $params an array of parameters to merge into the POST call
     * @param string $code   Merchant code provided by Snap*
     * @param string $key    Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapCallbacksPost($params, $code, $key)
    {
        $aPost = [
            'action' => 'get_callbacks',
            'code'   => $code,
            'return' => 'json',
        ];
        $aPost['mac'] = self::getCallbacksMac($aPost, $key);
        $aPost = array_merge($aPost, $params);

        return $aPost;
    }

    /**
     * Get the MAC for a Get Callbacks call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getCallbacksMac($aPost, $key)
    {
        $aMACParams = [
            'code'   => $aPost['code'],
            'action' => $aPost['action'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by the various Subscription calls.
     *
     * @param string $id     the subscription ID to be modified
     * @param string $action The specific API call to run
     * @param string $code   Merchant code provided by Snap*
     * @param string $key    Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapSubscriptionPost($id, $action, $code, $key)
    {
        $aPost = [
            'action'                   => $action,
            'code'                     => $code,
            'merchant_subscription_id' => $id,
            'return'                   => 'json',
        ];
        $aPost['mac'] = self::getSubscriptionMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the MAC for a Get Subscription call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getSubscriptionMac($aPost, $key)
    {
        $aMACParams = [
            'code'                     => $aPost['code'],
            'action'                   => $aPost['action'],
            'merchant_subscription_id' => $aPost['merchant_subscription_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by the Token checkout process.
     *
     * @param \EvoSnap\HostedPayments\Model\TokenCheckout $checkout
     * @param bool                                        $enable3d
     * @param string                                      $code
     * @param string                                      $key
     *
     * @return array
     */
    private static function getEvoSnapTokenCheckoutPost($checkout, $enable3d, $code, $key)
    {
        $returnUrl = $checkout->return_url;
        $cancelUrl = $checkout->cancel_url;

        $customer = $checkout->customer;
        $aCustomer = [
            'merchant_customer_id' => $customer->id,
            'first_name'           => Tools::getString($customer->first_name),
            'last_name'            => Tools::getString($customer->last_name),
            'phone'                => Tools::getString($customer->phone, 15),
            'email'                => Tools::getString($customer->email, 50),
        ];

        $token = $checkout->token;
        $aToken = [
            'merchant_token_id' => Tools::getString($token->id, 255),
            'currency_code'     => Tools::getString($token->currency_code),
            'auth_amount'       => Tools::getNumber($token->auth_amount),
            'enable_3d'         => Tools::getBoolean($enable3d),
        ];

        $aToken = array_merge($aToken, Tools::getAddress('billto', $token->billto_address));

        $aPost = [
            'action'          => $checkout->getAction(),
            'code'            => $code,
            'customer'        => $aCustomer,
            'token'           => $aToken,
            'return_url'      => $returnUrl,
            'cancel_url'      => $cancelUrl,
            'auto_return'     => '1',
            'checkout_layout' => $checkout->checkout_layout,
            'language'        => $checkout->language,
        ];
        $aPost['mac'] = self::getTokenCheckoutMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the MAC for a Token Checkout call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getTokenCheckoutMac($aPost, $key)
    {
        $aMACParams = [
            'code'              => $aPost['code'],
            'email'             => $aPost['customer']['email'],
            'merchant_order_id' => $aPost['token']['merchant_token_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by Get Tokens.
     *
     * @param array  $params an array of parameters to be merged into the call
     * @param string $code   Merchant code provided by Snap*
     * @param string $key    Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapTokensPost($params, $code, $key)
    {
        $aPost = [
            'action' => 'get_tokens',
            'code'   => $code,
            'return' => 'json',
        ];
        $aPost['mac'] = self::getTokensMac($aPost, $key);
        $aPost = array_merge($aPost, $params);

        return $aPost;
    }

    /**
     * Get the MAC for a Get Tokens call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getTokensMac($aPost, $key)
    {
        $aMACParams = [
            'code'   => $aPost['code'],
            'action' => $aPost['action'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by Get Token.
     *
     * @param string $id   the "Order ID" to be fetched
     * @param string $code Merchant code provided by Snap*
     * @param string $key  Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapTokenPost($id, $code, $key)
    {
        $aPost = [
            'action'            => 'get_token',
            'code'              => $code,
            'merchant_token_id' => $id,
            'return'            => 'json',
        ];
        $aPost['mac'] = self::getTokenMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the MAC for a Get Token call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getTokenMac($aPost, $key)
    {
        $aMACParams = [
            'code'              => $aPost['code'],
            'action'            => $aPost['action'],
            'merchant_token_id' => $aPost['merchant_token_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }

    /**
     * Compile the POST parameters used by Process Token.
     *
     * @param string                              $id       the "Order ID" to be fetched
     * @param \EvoSnap\HostedPayments\Model\Order $order    Pre-compiled Order object
     * @param string                              $code     Merchant code provided by Snap*
     * @param string                              $delegate (optional) parent or child merchant
     *                                                      code delegated to collect the processed payment
     * @param                                     $key      string Secret key provided by Snap*
     *
     * @return array compiled POST parameters
     */
    private static function getEvoSnapProcessTokenOrderPost($id, $order, $code, $key, $delegate)
    {
        $aOrder = [
            'merchant_order_id' => Tools::getString($order->id, 255),
            'total_subtotal'    => Tools::getNumber($order->total_subtotal),
            'total_tax'         => Tools::getNumber($order->total_tax),
            'total_shipping'    => Tools::getNumber($order->total_shipping),
            'total_discount'    => Tools::getNumber($order->total_discount),
            'total'             => Tools::getNumber($order->total),
            'ship_method'       => Tools::getString($order->ship_method),
        ];

        $aOrderLines = [];
        $products = $order->lines;
        for ($i = 0; $i < count($products); $i++) {
            $aOrderLines[$i] = self::getOrderItem($products[$i]);
        }

        $aOrder = array_merge($aOrder, Tools::getAddress('billto', $order->billto_address));
        if (isset($order->shipto_address)) {
            $aOrder = array_merge($aOrder, Tools::getAddress('shipto', $order->shipto_address));
        }

        $aPost = [
            'action'            => 'process_token',
            'code'              => $code,
            'merchant_token_id' => $id,
            'token_order'       => $aOrder,
            'token_order_item'  => $aOrderLines,
            'delegate'          => $delegate,
            'return'            => 'json',
        ];

        $aPost['mac'] = self::getProcessTokenOrderMac($aPost, $key);

        return $aPost;
    }

    /**
     * Get the MAC for a "Process Token" call.
     *
     * @param array  $aPost the post parameters for this call
     * @param string $key   the secret API key assigned by Snap
     *
     * @return string calculated MAC
     */
    private static function getProcessTokenOrderMac($aPost, $key)
    {
        $aMACParams = [
            'code'              => $aPost['code'],
            'action'            => $aPost['action'],
            'merchant_token_id' => $aPost['merchant_token_id'],
        ];

        return Tools::getMac($aMACParams, $key);
    }
}
