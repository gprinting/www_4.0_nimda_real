<?php
/**
 * Created by PhpStorm.
 * User: Hyeonsik Cho
 * Date: 2017-09-13
 * Time: 오전 10:42
 */

define("INC_PATH", $_SERVER["INC"]);
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . '/com/dprinting/ManualAcceptorDAO.inc');
include_once(INC_PATH . "/define/nimda/cypress_init.inc");
include_once($_SERVER["DOCUMENT_ROOT"] . "/application/libraries/JWT.php");
include_once($_SERVER["DOCUMENT_ROOT"] . "/application/helpers/acceptorhelper.php");
use \Firebase\JWT\JWT;

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$jwt = new JWT();
$dao = new ManualAcceptorDAO();
$fb = new FormBean();

$token = $fb->form("token");
$json = array();
$decoded = "";

$decoded = JWT::decode($token, acceptorhelper::$KEY, array('HS256'));

$param = array();
$param["table"] = "paper_op";
$param["prk"] = "paper_op_seqno";
$param["prkVal"] = $fb->form("order_id");

if($rs = $dao->deleteData($conn, $param)) {
    $json['result']['code'] = '0000';
    $json['result']['value'] = 'succeeded';
} else {
    $json['result']['code'] = '0001';
    $json['result']['value'] = 'fail';
}

echo json_encode($json);

?>