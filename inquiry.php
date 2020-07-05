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
        $partner_url          = ""; // provider url
        $pg_transactionid     = ""; // payment gateway transactionid
        $response = inquiry_TnGD($pg_transactionid, $partner_clientId, $partner_clientSecret, $partner_merchantId, $partner_url);
            break;
    case 'Alipay':
        $partner_id            = ""; // id between provider and payment gateway
        $partner_key           = ""; // secretkey between provider and payment gateway
        $partner_url           = ""; // provider url
        $pg_transactionid      = ""; // payment gateway transactionid
        $response = inquiry_Alipay($partner_id, $pg_transactionid, $partner_key, $partner_url);
            break;
    default:
        echo "E-Wallet not yet ready to integrate.";exit();
        break;
}

/*=================
Output API
=================*/
header("Content-Type: application/json;");
echo json_encode($response);

/**
* This function to inquiry latest status for Touch n Go Digital Transaction
*
* @param string $pg_transactionid       payment gateway transaction id
* @param string $partner_clientId       id between provider and payment gateway
* @param string $partner_clientSecret   secretkey between provider and payment gateway
* @param string $partner_merchantId     merchant name between provider and payment gateway
* @param string $partner_url            provider url
*
* @return string provider response
*/
function inquiry_TnGD($pg_transactionid, $partner_clientId, $partner_clientSecret, $partner_merchantId, $partner_url) {

    $keys = date('mdYHis');
    $tranID = sprintf("%06s", $pg_transactionid);
    $req_header = array(
                        "version"       => "1.0",
                        "function"      => "alipayplus.acquiring.order.query",
                        "clientId"      => $partner_clientId,
                        "clientSecret"  => $partner_clientSecret,
                        "reqTime"       => date('Y-m-d\TH:i:sP'),
                        "reqMsgId"      => $keys,
                        "reserve"       => (object)array()
                );

    $req_body = array(
                        "merchantId"        => $partner_merchantId,
                        "merchantTransId"   => $tranID
                    );

    $request = array(
                "request"   => array(
                                "head" => $req_header,
                                "body" => $req_body
                            ),
            );

    $PG_TnGD_Priv  = file_get_contents("/etc/pki/tls/certs/TnG/".$TNG_CERT);
    $algo = "RSA-SHA256";
    $signature = array("head" => $req_header, "body" => $req_body);
    $encoded_signature = json_encode($signature,1);

    openssl_sign($encoded_signature, $binary_signature, $PG_TnGD_Priv, $algo);
    $digital_signature = base64_encode($binary_signature);

    $request['signature'] = $digital_signature;
    $headers[] = "Content-Type: application/json";
    $req_TNG = json_encode($request, JSON_UNESCAPED_SLASHES);
    $response = Fn_cURL($partner_url, $method, $headers, $req_TNG);
    return $response;
}

/**
* This function to inquiry latest status for Alipay Txn
*
* @param string $rwst           transaction data from transaction table
* @param string $code status    code/error code/error description
* @param string $merchantID     merchant ID
* @param string $appCode        terminal application code
*
* @return array status code & payerID
*/
function inquiry_Alipay($partner_id, $pg_transactionid, $partner_key, $partner_url ) {
    $params = array(
                    "_input_charset" => "utf-8",
                    "service" => "alipay.acquire.overseas.query",
                    "partner" => $partner_id,
                    "partner_trans_id" => $pg_transactionid
                );

    ksort( $params );
    reset( $params );

    $params_string = $query_string = array();
    foreach( $params As $k => $v )
    {
        if( function_exists("mb_convert_encoding") )
        {
            $params_string[] = $k."=".mb_convert_encoding($v, "utf-8", "GBK");
            $query_string[] = $k."=".urlencode(mb_convert_encoding($v, "utf-8", "GBK"));
        }
        elseif( function_exists("iconv") )
        {
            $params_string[] = $k."=".iconv("GBK", "utf-8", $v);
            $query_string[] = $k."=".urlencode(iconv("GBK", "utf-8", $v));
        }
        else
        {
            $params_string[] = $k."=".$v;
            $query_string[] = $k."=".urlencode($v);
        }
    }

    $alipay_sign = md5( implode("&", $params_string).$partner_key );

    $partner_url = $partner_url."?".implode("&", $query_string)."&sign=".$alipay_sign."&sign_type=MD5";
    $header[] = "Content-Type: text/x-www-form-urlencoded";
    $response = Fn_cURL($partner_url, $method, $headers);
    return $response;
}

/**
* This function to send request to the Provider server
*
* @param string $URL        provider end point
* @param string $method     cURL method
* @param string $body       request to provider
* @param string $header     header to provider
*
* @return string result curl
*/
function Fn_cURL($URL, $method, $header = [], $body = [] ) {

    $ch = curl_init( $URL );
    curl_setopt( $ch, CURLOPT_URL               , $URL );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER    , TRUE );
    curl_setopt( $ch, CURLOPT_ENCODING          , "" );
    curl_setopt( $ch, CURLOPT_MAXREDIRS         , 10 );
    curl_setopt( $ch, CURLOPT_TIMEOUT           , 30 );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION    , TRUE );
    curl_setopt( $ch, CURLOPT_HTTP_VERSION      , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST     , $method );
    if (!empty($header)) {
        curl_setopt( $ch, CURLOPT_HTTPHEADER        , array( $header ) );
    }
    if ($method == "POST") {
        curl_setopt( $ch, CURLOPT_POSTFIELDS        , $body );
    }

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
            mail( "email", "[REQUEST] API ERROR!!!", print_r( $st,1 ) );
        }

        // Send Alert to merchant
        $response = "Curl Error";
        return $response;
    }
    return $response;
}

