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
        $amount               = ""; // transaction amount
        $partner_clientId     = ""; // id between provider and payment gateway
        $partner_clientSecret = ""; // secretkey between provider and payment gateway
        $pg_transactionid     = ""; // payment gateway transactionid
        $partner_merchantId   = ""; // merchant name between provider and payment gateway
        $acquirementId        = ""; // get from the response payment transaction
        $partner_url          = ""; // provider url
        $response = refund_TnGD($amount, $partner_clientId, $partner_clientSecret, $pg_transactionid, $partner_merchantId, $acquirementId, $partner_url);
            break;
    case 'Alipay':
        $partner_id            = ""; // id between provider and payment gateway
        $pg_transactionid      = ""; // payment gateway transactionid
        $partner_key           = ""; // secretkey between provider and payment gateway
        $partner_url           = ""; // provider url
        $pg_transactionid      = ""; // payment gateway transactionid
        $acquirementId         = ""; // get this value from response payment
        $response = refund_Alipay($partner_id, $pg_transactionid, $partner_merchantId, $acquirementId);
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
* @param string $amount                 transaction amount from payment gateway
* @param string $partner_clientId       id between provider and payment gateway
* @param string $partner_clientSecret   secretkey between provider and payment gateway
* @param string $pg_transactionid       payment gateway transaction id
* @param string $partner_merchantId     merchant name between provider and payment gateway
* @param string $acquirementId          get from the response payment transaction
* @param string $partner_url            provider url
*
* @return string provider response
*/
function refund_TnGD($amount, $partner_clientId, $partner_clientSecret, $pg_transactionid, $partner_merchantId, $acquirementId, $partner_url) {
    $keys = date('mdYHis');
    $new_amount = $amount * 100;
    $req_header = array(
                            "version"       => "1.0",
                            "function"      => "alipayplus.acquiring.order.refund",
                            "clientId"      => $partner_clientId,
                            "clientSecret"  => $partner_clientSecret,
                            "reqTime"       => date('Y-m-d\TH:i:sP'),
                            "reqMsgId"      => $keys,
                            "reserve"       => (object)array()
                    );

    $req_body = array(
                        "requestId"         => $pg_transactionid,
                        "merchantId"        => $partner_merchantId,
                        "acquirementId"     => $acquirementId,
                        "refundAmount"      =>  (object) array(
                                                            'currency' => "MYR",
                                                            'value' => $new_amount
                                                    ),
                );

    $request = array("request"   => array( "head" => $req_header, "body" => $req_body),);

    # Signature to TNG
    $PG_TnGD_Priv  = file_get_contents("/etc/pki/tls/certs/TnG/".$cert); // key exchange between  payment gateway and provider
    $algo = "RSA-SHA256"; // hashing algo
    $signature = array("head" => $req_header, "body" => $req_body);
    $encoded_signature = json_encode($signature,1);
    openssl_sign($encoded_signature, $binary_signature, $PG_TnGD_Priv, $algo);
    $digital_signature = base64_encode($binary_signature);
    $request['signature'] = $digital_signature;
    $req_TNG = json_encode($request, JSON_UNESCAPED_SLASHES);
    $headers[] = "Content-Type: application/json";

    $response = Fn_cURL($partner_url, "POST", $headers, $req_TNG);
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
function refund_Alipay($amount, $partner_id, $pg_transactionid, $partner_url) {
    $new_amount = $amount * 100;
    $params = array(
                          'service'               => 'alipay.acquire.overseas.spot.refund',
                          'partner'               => $partner_id,
                          '_input_charset'        => 'utf-8',
                          'partner_trans_id'      => $pg_transactionid,
                          'partner_refund_id'     => $pg_transactionid,
                          'refund_amount'         => $new_amount,
                          'currency'              => "MYR",
                          'notify_url'            => '',
                          'refund_reson'          => ''
                  );

    ksort($params);

    $post_params = array();
    foreach( array_filter($params) as $k => $v ) {
          $post_params[] = $k."=".$v;
          $query_string[] = $k."=".urlencode($v);
    }

    $sign = md5( implode("&",$post_params).$partner_key );
    $params['sign'] = $sign;
    $params['sign_type'] = "MD5";
    $partner_url = $partner_url."?".implode("&", $query_string)."&sign=".$sign."&sign_type=MD5";
    $response = Fn_cURL($partner_url, "GET");
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

