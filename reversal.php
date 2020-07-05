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
        $reverse_url          = ""; // provider url
            break;
    case 'Alipay':
        $partner_id            = ""; // id between provider and payment gateway
        $partner_key           = ""; // secretkey between provider and payment gateway
        $secondary_merchant_id = ""; // merchant name between provider and payment gateway
        $partner_mcc           = ""; // merchant category code between payment gateway and provider
        $reverse_url           = ""; // provider url
            break;
    default:
        echo "E-Wallet not yet ready to integrate.";exit();
        break;
}

/*=================
Process from Payment Gateway to Provider
=================*/
if ($ewallet_provider = "Touch n Go Digital") {
    
    # Header to TnGD
    $keys = date('mdYHis');
    $req_header = array(
                        "version"       => "1.0",
                        "function"      => "alipayplus.acquiring.order.cancel",
                        "clientId"      => $partner_clientId,
                        "clientSecret"  => $partner_clientSecret,
                        "reqTime"       => date('Y-m-d\TH:i:sP'),
                        "reqMsgId"      => $keys,
                        "reserve"       => (object)array()
                );

    # Body to TNG
    $req_body = array(
                        "merchantId"    => $partner_merchantId,
                        "merchantTransId" => $transactionID // recorded transaction ID from PG side.
                    );

    $request = array( "request"   => array( "head" => $req_header, "body" => $req_body),);

    # Signature to TNG
    $PG_TnGD_Priv  = file_get_contents("/etc/pki/tls/certs/TnG/".$cert); // key exchange between  payment gateway and provider
    $algo = "RSA-SHA256"; // hashing algo
    $signature = array("head" => $req_header, "body" => $req_body);
    $encoded_signature = json_encode($signature,1);
    openssl_sign($encoded_signature, $binary_signature, $PG_TnGD_Priv, $algo);
    $digital_signature = base64_encode($binary_signature);
    $request['signature'] = $digital_signature;
    $request = json_encode($request, JSON_UNESCAPED_SLASHES);

} elseif ($ewallet_provider = "Alipay") {

        $timestamp = date("YmdHis");
        $params = array(
                            '_input_charset'    => 'utf-8',
                            'out_trade_no'      => $transactionID, // recorded transaction ID from PG side.
                            'partner'           => $partner_id,
                            'service'           => 'alipay.acquire.cancel',
                            'timestamp'         => $timestamp
                        );

        ksort($params);
        reset($params);

        $request = array();
        foreach( array_filter($params) as $k => $v ) {
            $post_params[] = $k."=".$v;
            $query_string[] = $k."=".urlencode($v);
        }

        $sign = md5( implode("&",$request).$partner_key );
        $reverse_url = $reverse_url."?".implode("&", $query_string)."&sign=".$sign."&sign_type=MD5",
}

/*=================
cURL to Provider
=================*/
$ch = curl_init( $reverse_url );
curl_setopt( $ch, CURLOPT_URL               , $reverse_url );
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
        mail( "email", "[REQUEST] ".$ewallet_provider." Payment API ERROR!!!", print_r( $st,1 ) );
    }

    // Send Alert to merchant
    // Log the error
    exit();
}

/*=================
Output API
=================*/
header("Content-Type: application/json;");
echo json_encode($response);