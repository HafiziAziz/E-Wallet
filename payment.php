<?php

/*=================
Malaysia E-Wallet provider
$provider = [Touch n Go Digital, Alipay, WeChatPay, UnionPay, RazerPay, GrabPay, Boost];
=================*/
$ewallet_provider = "dummy";

/*=================
Put all the credential here
=================*/
switch($ewallet_provider) {
    case 'Touch n Go Digital':
        $partner_clientId     = ""; // id between provider and payment gateway
        $partner_clientSecret = ""; // secretkey between provider and payment gateway
        $partner_merchantId   = ""; // merchant name between provider and payment gateway
        $partner_productCode  = ""; // product id between provider and payment gateway
        $merchant_mcc         = ""; // merchant category code between merchant and payment gateway
            break;
    case 'Alipay':
        $partner_id            = ""; // id between provider and payment gateway
        $partner_key           = ""; // secretkey between provider and payment gateway
        $secondary_merchant_id = ""; // merchant name between provider and payment gateway
        $partner_mcc           = ""; // merchant category code between payment gateway and provider
            break;
    default:                // SANDBOX
        $mid_username = "WS4900000027._.1";
        $mid_password = "H:39q#ZGjA";
        $mid_sslkeypasswd = "JUx<+3f4yN";
        $mid_pemfile = "WS4900000027._.1.pem";
        $mid_keyfile = "WS4900000027._.1.key";
        break;
}