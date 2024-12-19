<?
//ini_set("display_errors", 1);
define("INC_PATH", $_SERVER["INC"]);
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/job/nimda/calcul_mng/tab/SalesTabListDAO.inc");
include_once(INC_PATH . '/com/nexmotion/common/util/nimda/pageLib.inc');

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$fb = new FormBean();
$dao = new SalesTabListDAO();

//한페이지에 출력할 게시물 갯수
$list_num = $fb->form("listSize"); 

//현재 페이지
$page = $fb->form("page");

//리스트 보여주는 갯수 설정
if (!$fb->form("listSize")) {
    $list_num = 30;
}

//블록 갯수
$scrnum = 5; 

// 페이지가 없으면 1 페이지
if (!$page) {
    $page = 1; 
}

$s_num = $list_num * ($page-1);

$sell_site = $fb->form("sell_site");
$member_dvs = $fb->form("member_dvs");
$year = $fb->form("year");
$mon = $fb->form("mon");
$member_seqno = $fb->form("member_seqno");

$param = array();
$param["s_num"] = $s_num;
$param["list_num"] = $list_num;
$param["sell_site"] = $sell_site;
$param["member_dvs"] = $member_dvs;
$param["year"] = $year;
$param["mon"] = $mon;
$param["member_seqno"] = $member_seqno;
$param["dvs"] = "SEQ";

if ($fb->form("search")) {
    $param["search"] = $fb->form("search");
    $param["search_dvs"] = $fb->form("search_dvs");
}

$rs = $dao->selectUnissuedList($conn, $param);
$list = makeUnissuedListHtml($conn, $dao, $rs, $param);

$param["dvs"] = "COUNT";
$count_rs = $dao->selectUnissuedList($conn, $param);
$rsCount = $count_rs->RecordCount();

$paging = mkDotAjaxPage($rsCount, $page, $scrnum, $list_num, "moveUnissuedPage");
//$total_rs = $dao->selectUnissuedSum($conn, $param);

$param["dvs"] = "SUM";
$total_rs = $dao->selectUnissuedList($conn, $param);

$param["dvs"] = "SUM2";
$total_rs2 = $dao->selectUnissuedList($conn, $param);


$member_cnt = "0";
$pay_total = "0";
$card_total = "0";
$cash_total = "0";

if ($count_rs->fields["cnt"]) {
    $member_cnt = number_format($count_rs->fields["cnt"]);
}

//매출합계가 있을때
if ($total_rs->fields["pay_price"]) {

    $pay_total = $total_rs->fields["pay_price"];

}

//조정합계가 있을때
if ($total_rs->fields["adjust_price"]) {
    $adjust_price = $total_rs->fields["adjust_price"];
}

$total = $pay_total - $adjust_price;

echo $list . "♪♭@"  . $paging . "♪♭@" . $member_cnt . "♪♭@" . 
     number_format($member_cnt) . "♪♭@" .
     number_format($pay_total) . "♪♭@" .
     number_format($adjust_price). "♪♭@" .
    number_format($total);



$conn->close();
?>