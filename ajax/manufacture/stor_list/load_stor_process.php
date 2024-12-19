
<?
define("INC_PATH", $_SERVER["INC"]);
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/job/nimda/manufacture/cutting_mng/CuttingListDAO.inc");
include_once(INC_PATH . '/com/nexmotion/common/util/nimda/pageLib.inc');

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$fb = new FormBean();
$dao = new CuttingListDAO();


$from_date = $fb->form("date_from");
$from_time = "";
$to_date = $fb->form("date_to");
$to_time = "";

if ($from_date) {
    $from_time = $fb->form("date_from");
    $from = $from_date . " " . "00:00:00";
}

if ($to_date) {
    $to_time = " " . $fb->form("date_to") + 1;
    $to = $to_date . " " . "23:59:59";
}

$param = array();
$param["preset_cate"] = $fb->form("preset_cate");
$param["typset_num"] = $fb->form("typset_num");
$param["state"] = $fb->form("state");
$param["extnl_etprs_seqno"] = $fb->form("extnl_etprs_seqno");
$param["date_cnd"] = $fb->form("date_cnd");
$param["from"] = $from;
$param["to"] = $to;
$param["dvs"] = "SEQ";

$sheet_typset_seqno = explode('|',$fb->form("typset_num"));

foreach ($sheet_typset_seqno as $tmp_sheet_typset) {

    if($tmp_sheet_typset == ""){
        continue;
    }

    $param["typset_num"] = $tmp_sheet_typset;
    $param["after"] = "y";
    $rs = $dao->selectCuttingProcess2($conn, $param);
    $list1 .= makeCuttingProcessHtml2($rs, $param);

    $param["after"] = "n";
    $rs = $dao->selectCuttingProcess2($conn, $param);
    $list2 .= makeCuttingProcessHtml2($rs, $param);
}
/*
//스티커는 무조건 통과 
$param["after"] = "n";
$rs = $dao->selectCuttingProcess2($conn, $param);
$list1 .= makeCuttingProcessHtml2($rs, $param);

$param["after"] = "y";
$rs = $dao->selectCuttingProcess2($conn, $param);
$list2 .= makeCuttingProcessHtml2($rs, $param); 
*/


echo $list1 . "♪" . $list2  ;
$conn->close();
?>
