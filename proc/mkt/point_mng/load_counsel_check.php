<?
define("INC_PATH", $_SERVER["INC"]);
include_once($_SERVER["DOCUMENT_ROOT"] . "/common/sess_common.php");
include_once(INC_PATH . "/com/nexmotion/job/front/common/FrontCommonDAO.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . '/com/nexmotion/common/util/nimda/pageLib.inc');

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$dao = new FrontCommonDAO();
$fb = new FormBean();


//한페이지에 출력할 게시물 갯수
$list_num = $fb->form("showPage"); 

// $conn->debug = 1;

//현재 페이지
$page = $fb->form("page");

//리스트 보여주는 갯수 설정
if (!$fb->form("showPage")) {
    $list_num = 30;
}

//블록 갯수
$scrnum = 5; 

// 페이지가 없으면 1 페이지
if (!$page) {
    $page = 1; 
}

$s_num = $list_num * ($page-1);
// 세션 불러온다.


$param = array();
$param["s_num"] = $s_num;
$param["list_num"] = $list_num;
$param["sell_ch"] = $fb->form("sell_ch");
$param["search_dvs"] = $fb->form("search_dvs");
$param["man_list"] = $fb->form("man_list");
$param["state_list"] = $fb->form("state_list");
$datefrom = explode("/", $fb->form("date_from"));
$param["date_from"] = $datefrom[2]."-".$datefrom[0]."-".$datefrom[1];
$dateto = explode("/", $fb->form("date_to"));
$param["date_to"] = $dateto[2]."-".$dateto[0]."-".$dateto[1];
$param["member_seqno"] = $fb->form("member_seqno");

//print_r($param);

$rs = $dao->selectCounselInfo($conn, "SEQ", $param);
//print_r($rs);
$count_rs = $dao->selectCounselInfo($conn, "COUNT", $param);
$rsCount = $count_rs->fields["cnt"];

$param["cnt"] = $rsCount;

//echo $param["cnt"];

$list = makeCounselHtml($conn, $dao, $rs, $param);

//$rs = $dao->selectMemberInfo2($conn, "SEQ", $param);
//$point = testpointsum($conn, $dao, $rs, $param);
$paging = mkDotAjaxPage($rsCount, $page, $scrnum, $list_num, "movePage3");


echo $list . "♪" . $paging  . "♪" . $rsCount;

$conn->close();
?>
