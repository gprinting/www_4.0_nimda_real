<?php

$id = uniqid();

$post_data = array();
$post_data["api_code"] = "0204";
$post_data["custom_auth_code"] = "REAL95840adba53c44558a9f68984b37ac74";
$post_data["custom_auth_token"] = "8tg3AIVeORCaJ0ZLJFLDhw==";
$post_data["dev_yn"] = "N";
$post_data["goods_code"] = "G00001284065";
$post_data["mms_msg"] = "감사쿠폰";
$post_data["mms_title"] = "회원가입 감사합니다.";
$post_data["callback_no"] = "0222722901";
$post_data["phone_no"] = "01050157915";
$post_data["tr_id"] = $id;
$post_data["user_id"] = "webdesign@adsland.com";
$post_data["gubun"] = "I";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://bizapi.giftishow.com/bizApi/send");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

$headers = array();
$response = curl_exec($ch);
//$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$post_data1 = array();
$post_data1["api_code"] = "0202";
$post_data1["custom_auth_code"] = "REAL95840adba53c44558a9f68984b37ac74";
$post_data1["custom_auth_token"] = "8tg3AIVeORCaJ0ZLJFLDhw==";
$post_data1["dev_yn"] = "N";
$post_data1["tr_id"] = $id;
$post_data1["user_id"] = "webdesign@adsland.com";

$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, "https://bizapi.giftishow.com/bizApi/cancel");
curl_setopt($ch1, CURLOPT_POST, true);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_POSTFIELDS, $post_data1);

$headers = array();
$response1 = curl_exec($ch1);
//$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch1);
$decoded = json_decode($response);
var_dump($decoded->result->result->couponImgUrl);
//var_dump($response1);
echo $id;



?>