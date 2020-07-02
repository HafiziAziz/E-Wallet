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
        $partner_url          = ""; // provider url
            break;
    case 'Alipay':
        $partner_id            = ""; // id between provider and payment gateway
        $partner_key           = ""; // secretkey between provider and payment gateway
        $secondary_merchant_id = ""; // merchant name between provider and payment gateway
        $partner_mcc           = ""; // merchant category code between payment gateway and provider
        $partner_url           = ""; // provider url
            break;
    default:
        echo "E-Wallet not yet ready to integrate.";exit();
        break;
}

/*=================
Process from Payment Gateway to Provider
=================*/
if ($ewallet_provider = "Touch n Go Digital") {
    
    # Header to TNGD
    $keys = date('mdYHis');
    $headers = "Content-Type: application/json";
    $req_header = array(
                        "version"       => "1.0",
                        "function"      => "alipayplus.retail.pay",
                        "clientId"      => $clientId,
                        "clientSecret"  => $clientSecret,
                        "reqTime"       => date('Y-m-d\TH:i:sP'),
                        "reqMsgId"      => $keys,
                        "reserve"       => (object)array()
                );

    # Body to TNG
    $new_amount = clean_amt($amount) * 100;
    $req_body = array(
        
                    "merchantId"     => $partner_merchantId, // merchant name between provider and payment gateway
                    "productCode"    => $partner_productCode, // product id between provider and payment gateway
                    "mcc"            => (object)array(),
                    "authCodeType"   => "BAR_CODE",
                    "authCode"       => $authCode, // user E-WALLET barcode
                    "order"          => (object) array(
                                                        'orderTitle' => "Payment ".$description, // merchant product desc
                                                        'orderAmount' => (object) array(
                                                                                'currency' => "MYR",
                                                                                'value' => (string) $new_amount // merchant product amount
                                                                        ),
                                                        'merchantTransId' => $card['ordernum'] // Payment gateway unique ID
                                                    ),
                    "envInfo"        => (object) array(
                                                        'merchantTerminalId' => $merchantTerminalId, // merchant terminal id
                                                        'terminalType'       => "SYSTEM",
                                                        'orderTerminalType'  => "SYSTEM"
                                                ),
                    "extendInfo"    => (object) array(
                                                        'merchantId'        => $merchantTerminalId, // merchant terminal id
                                                        'merchantName'      => $merchantName, // merchant name/shop name
                                                        'Tid'               => $Tid, // merchant storeid
                                                        'shopName'          => $merchantName, // merchant store name
                                                        'MCC'               => $merchant_mcc, // merchant category code
                                                        'merchantStreet'    => $merchantStreet, // merchant address
                                                        'merchantState'     => $merchantState, // merchant address
                                                        'merchantCity'      => $merchantCity, // merchant address
                                                        'merchantPostcode'  => $merchantPostcode, // merchant address
                                                        'brand'             => $merchantName // merchant brand
                                                ),
            );

    $request = array( "request"   => array( "head" => $req_header, "body" => $req_body),);

    # Signature to TNG
    $MOLPay_TnGD_Priv  = file_get_contents("/etc/pki/tls/certs/TnG/".$cert); // key exchange between  payment gateway and provider
    $algo = "RSA-SHA256"; // hashing algo
    $signature = array("head" => $req_header, "body" => $req_body);
    $encoded_signature = json_encode($signature,1);
    openssl_sign($encoded_signature, $binary_signature, $MOLPay_TnGD_Priv, $algo);
    $digital_signature = base64_encode($binary_signature);
    $request['signature'] = $digital_signature;
    $req_TNG = json_encode($request, JSON_UNESCAPED_SLASHES);

    # Log Request to TNG
    $logname = "log PATH";
    $tmpfp = fopen( $logname, "a+" );
    $tmpfp_msg = $_SERVER['REMOTE_ADDR']."\t".$_SERVER['HTTP_ORIGIN']."\t".$_SERVER['HTTP_REFERER']."\n".print_r(json_decode($req_TNG),1);
    fwrite($tmpfp, "Request to TNG: \n".date('Y-m-d H:i:s')."\tLine:".__LINE__."\t".$tmpfp_msg."\n===========\n" );
    fclose($tmpfp);
    $payment_URL = $partner_url;
} elseif ($ewallet_provider = "Alipay") {
    # Header to Alipay
    $headers = "Content-Type: text/x-www-form-urlencoded";

    # Body to Alipay
    $request =    [
                    "_input_charset" => "utf-8",
                    "service" => "alipay.acquire.overseas.spot.pay",
                    "partner" => $partner_id,
                    "alipay_seller_id" => $partner_id,
                    "trans_name" => "Payment: ".$description,
                    "partner_trans_id" => $card['ordernum'],
                    "currency" => $currencyCode,
                    "trans_amount" => $amount,
                    "buyer_identity_code" => $authCode,
                    "identity_code_type" => $alipay_authCodeType,
                    "trans_create_time" => date("YmdHis"),
                    "biz_product" => "OVERSEAS_MBARCODE_PAY",
                    "extend_info" => '{"secondary_merchant_industry":"'.$partner_mcc.'","secondary_merchant_name":"'.$info->merchant_name.'","secondary_merchant_id":"'.$secondary_merchant_id.'","terminal_id":"'.$terminalId.'","store_name":"'.$secondary_store_name.'","store_id":"'.$merchantStoreId.'"}'
                ];

    ksort( $request );
    reset( $request );

    # Log Request to Alipay
    $tmpfp = fopen( $logname, "a+" );
    $tmpfp_msg = $_SERVER['REMOTE_ADDR']."\t".$_SERVER['HTTP_ORIGIN']."\t".$_SERVER['HTTP_REFERER']."\n".print_r($request,1);
    fwrite($tmpfp, "Request to Alipay: \n".date('Y-m-d H:i:s')."\tLine:".__LINE__."\t".$tmpfp_msg."\n===========\n" );
    fclose($tmpfp);
      
    $params_string = $query_string = array();
    foreach( $request As $k => $v ) {
      if( function_exists("mb_convert_encoding") ) {
        $params_string[] = $k."=".mb_convert_encoding($v, "utf-8", "GBK");
        $query_string[] = $k."=".urlencode(mb_convert_encoding($v, "utf-8", "GBK"));
      } elseif( function_exists("iconv") ) {
        $params_string[] = $k."=".iconv("GBK", "utf-8", $v);
        $query_string[] = $k."=".urlencode(iconv("GBK", "utf-8", $v));
      } else {
        $params_string[] = $k."=".$v;
        $query_string[] = $k."=".urlencode($v);
      }
    }

    # Signature to Alipay
    $alipay_sign = md5( implode("&", $params_string).$partner_key );
    $payment_URL = $partner_url."?".implode("&", $query_string)."&sign=".$alipay_sign."&sign_type=MD5";
}

/*=================
cURL to Provider
=================*/
$ch = curl_init( $payment_URL );
curl_setopt( $ch, CURLOPT_URL               , $payment_URL );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER    , TRUE );
curl_setopt( $ch, CURLOPT_ENCODING          , "" );
curl_setopt( $ch, CURLOPT_MAXREDIRS         , 10 );
curl_setopt( $ch, CURLOPT_TIMEOUT           , 30 );
curl_setopt( $ch, CURLOPT_HTTP_VERSION      , CURL_HTTP_VERSION_1_1 );
curl_setopt( $ch, CURLOPT_CUSTOMREQUEST     , "POST" );
curl_setopt( $ch, CURLOPT_POSTFIELDS        , $request );
curl_setopt( $ch, CURLOPT_HTTPHEADER        , array( $headers ) );
$response = curl_exec( $ch );

if (curl_errno( $ch ) || empty($response)) {
    $st = array(
        "curl_errcode"        => curl_errno($ch),
        "curl_message"        => curl_error($ch),
        "curl_info"           => curl_getinfo($ch),
        "data"                => $response
    );

    if ( empty($response) ) {
        // Send Alert to internal
    } else {
        // Send Alert to internal
        mail( "email", "[REQUEST] TNGD Payment API ERROR!!!", print_r( $st,1 ) );
    }

    // Send Alert to merchant
    return false;
}

/*=================
Output API
=================*/
header("Content-Type: application/json;");
echo json_encode($response);