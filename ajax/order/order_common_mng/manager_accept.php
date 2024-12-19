<?
define(INC_PATH, $_SERVER["INC"]);
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . '/com/dprinting/MoamoaDAO.inc');
include_once(INC_PATH . "/com/nexmotion/job/front/common/FrontCommonDAO.inc");

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$dao = new MoamoaDAO();
$ndao = new FrontCommonDAO();
$fb = new FormBean();

$session = $fb->getSession();
$param = array();
$param1 = array();
$param['empl_id'] = $param1['empl_id'] = $session["id"];
$param['state'] = $fb->form("state");
$param['kind'] = $fb->form("kind");
$member =  $fb->form("member");

$param1["kind"] = "포인트담당자 배정";
$param1["before"] = "";
$param1["after"] = "";

$kind = $fb->form("kind");

$ordernums = explode("|", $fb->form("ordernums"));

if($param['empl_id'] == null || $param['empl_id'] == "") exit;

if($kind == "clear")
    $param['empl_id'] = "";

$allocated = "1";
foreach($ordernums as $ordernum) {
   // $param1['order_common_seqno'] =  $dao->selectOrderCommonSeqnoByOrderNum($conn, $ordernum);
   // $dao->insertOrderInfoHistory($conn, $param1);



    $param['ordernum'] = $ordernum;
    $param['member'] = $member;
// 회원 관리자 추가 
    $rs = $dao->updateManager($conn, $param);

    $param2['send_points'] = 0;
    $param2['member_seqno'] = $ordernum;
    $param2['mb_id_point'] =  $ordernum;
// 포인트업데이트
    $result = $ndao->updateManagePoint($conn, $param2, $ndao);

    if($rs == 0) $allocated = "0";
}

echo $allocated;

?>