<?php

namespace Omnipay\PayPal\Message;

/**
 * PayPal Express Authorize Request
 */
class ExpressAuthorizeRequest extends AbstractRequest
{

    const DEFAULT_CALLBACK_TIMEOUT = 5;

    public function setCallback($callback)
    {
        return $this->setParameter('callback', $callback);
    }

    public function getCallback()
    {
        return $this->getParameter('callback');
    }

    public function setCallbackTimeout($callbackTimeout)
    {
        return $this->setParameter('callbackTimeout', $callbackTimeout);
    }

    public function getCallbackTimeout()
    {
        return $this->getParameter('callbackTimeout');
    }

    /**
     * @param int    $index
     * @param string $name
     * @param float  $amount
     * @param bool   $isDefault
     * @param string $label
     */
    public function setShippingOption($index, $name, $amount, $isDefault, $label = null)
    {
        $data['L_SHIPPINGOPTIONNAME' . $index] = $name;
        $data['L_SHIPPINGOPTIONAMOUNT' . $index] = number_format($amount, 2);
        $data['L_SHIPPINGOPTIONISDEFAULT' . $index] = $isDefault ? '1' : '0';

        if (!is_null($label)) {
            $data['L_SHIPPINGOPTIONLABEL' . $index] = $name;
        }

        $currentShippingOptions = $this->getParameter('shippingOptions');
        if (empty($currentShippingOptions)) {
            $currentShippingOptions = array();
        }

        $currentShippingOptions[$index] = $data;

        $this->setParameter('shippingOptions', $currentShippingOptions);
    }

    /**
     * Multi-dimensional array of shipping options, containing:
     *  - index, name, amount, isDefault, label
     * index is 0-based as per PayPal's docs. label is optional
     *
     * @param array $data
     */
    public function setShippingOptions($data)
    {
        $this->setParameter('shippingOptions', $data);
    }

    public function getShippingOptions()
    {
        return $this->getParameter('shippingOptions');
    }

    public function getData()
    {
        $this->validate('amount', 'returnUrl', 'cancelUrl');

        $data = $this->getBaseData();
        $data['METHOD'] = 'SetExpressCheckout';
        $data['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Authorization';
        $data['PAYMENTREQUEST_0_AMT'] = $this->getAmount();
        $data['PAYMENTREQUEST_0_CURRENCYCODE'] = $this->getCurrency();
        $data['PAYMENTREQUEST_0_INVNUM'] = $this->getTransactionId();
        $data['PAYMENTREQUEST_0_DESC'] = $this->getDescription();

        // pp express specific fields
        $data['SOLUTIONTYPE'] = $this->getSolutionType();
        $data['LANDINGPAGE'] = $this->getLandingPage();
        $data['RETURNURL'] = $this->getReturnUrl();
        $data['CANCELURL'] = $this->getCancelUrl();
        $data['HDRIMG'] = $this->getHeaderImageUrl();
        $data['BRANDNAME'] = $this->getBrandName();
        $data['NOSHIPPING'] = $this->getNoShipping();
        $data['ALLOWNOTE'] = $this->getAllowNote();
        $data['ADDROVERRIDE'] = $this->getAddressOverride();
        $data['LOGOIMG'] = $this->getLogoImageUrl();
        $data['CARTBORDERCOLOR'] = $this->getBorderColor();
        $data['LOCALECODE'] = $this->getLocaleCode();
        $data['CUSTOMERSERVICENUMBER'] = $this->getCustomerServiceNumber();

        $callback = $this->getCallback();

        if (!empty($callback)) {
            $data['CALLBACK']        = $callback;
            // callback timeout MUST be included and > 0
            $timeout = $this->getCallbackTimeout();

            $data['CALLBACKTIMEOUT'] = $timeout > 0 ? $timeout : self::DEFAULT_CALLBACK_TIMEOUT;

            // if you're using a callback you MUST set shipping option(s)
            $shippingOptions = $this->getShippingOptions();

            if (!empty($shippingOptions)) {
                foreach ($shippingOptions as $shipping) {
                    $index     = $shipping['index'];
                    $name      = $shipping['name'];
                    $isDefault = $shipping['isDefault'];
                    $amount    = $shipping['amount'];
                    $label     = isset($shipping['label']) ? $shipping['label'] : '';

                    $data['L_SHIPPINGOPTIONNAME' . $index]      = $name;
                    $data['L_SHIPPINGOPTIONAMOUNT' . $index]    = number_format($amount, 2);
                    $data['L_SHIPPINGOPTIONISDEFAULT' . $index] = $isDefault ? '1' : '0';

                    if (!empty($label)) {
                        $data['L_SHIPPINGOPTIONLABEL' . $index] = $label;
                    }
                }
            }
        }

        $data['MAXAMT'] = $this->getMaxAmount();
        $data['PAYMENTREQUEST_0_TAXAMT'] = $this->getTaxAmount();
        $data['PAYMENTREQUEST_0_SHIPPINGAMT'] = $this->getShippingAmount();
        $data['PAYMENTREQUEST_0_HANDLINGAMT'] = $this->getHandlingAmount();
        $data['PAYMENTREQUEST_0_SHIPDISCAMT'] = $this->getShippingDiscount();
        $data['PAYMENTREQUEST_0_INSURANCEAMT'] = $this->getInsuranceAmount();
        $data['PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID'] = $this->getSellerPaypalAccountId();

        $card = $this->getCard();
        if ($card) {
            $data['PAYMENTREQUEST_0_SHIPTONAME'] = $card->getName();
            $data['PAYMENTREQUEST_0_SHIPTOSTREET'] = $card->getAddress1();
            $data['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $card->getAddress2();
            $data['PAYMENTREQUEST_0_SHIPTOCITY'] = $card->getCity();
            $data['PAYMENTREQUEST_0_SHIPTOSTATE'] = $card->getState();
            $data['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $card->getCountry();
            $data['PAYMENTREQUEST_0_SHIPTOZIP'] = $card->getPostcode();
            $data['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = $card->getPhone();
            $data['EMAIL'] = $card->getEmail();
        }

        $data = array_merge($data, $this->getItemData());

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ExpressAuthorizeResponse($this, $data);
    }
}
