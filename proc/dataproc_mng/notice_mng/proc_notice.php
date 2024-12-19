<?
define("INC_PATH", $_SERVER["INC"]);
include_once(INC_PATH . "/define/nimda/common_config.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/job/nimda/dataproc_mng/bulletin_mng/BulletinMngDAO.inc");
include_once(INC_PATH . "/com/nexmotion/job/nimda/file/FileAttachDAO.inc");

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();
$conn->StartTrans();

$fb = new FormBean();
$bulletinDAO = new BulletinMngDAO();
$fileDAO = new FileAttachDAO();
$check = 1;

$param = array();
$param["table"] = "notice";
//공지 종류 
$param["col"]["dvs"] = $fb->form("dvs");
//제목
$param["col"]["title"] = $fb->form("title");
//내용
$param["col"]["cont"] = $fb->form("cont");

//공지사항 수정
if ($fb->form("notice_seq")) {

    $param["prk"] = "notice_seqno";
    $param["prkVal"] = $fb->form("notice_seq");

    $result = $bulletinDAO->updateData($conn, $param);
    if (!$result) $check = 0;

    if ($fb->form("upload_file")) {

        //공지사항 파일 삭제
        $param = array();
        $param["table"] = "notice_file";
        $param["prk"] = "notice_seqno";
        $param["prkVal"] = $fb->form("notice_seqno");

        $result = $bulletinDAO->deleteData($conn, $param);
        if (!$result) $check = 0;

        //파일 업로드 경로
        $param =  array();
        $param["file_path"] = SITE_DEFAULT_NOTICE_FILE;
        $param["tmp_name"] = $_FILES["upload_btn"]["tmp_name"];
        $param["origin_file_name"] = $_FILES["upload_btn"]["name"];

        //파일을 업로드 한 후 저장된 경로를 리턴한다.
        $result= $fileDAO->upLoadFile($param);

        //공지사항 파일 추가
        $param = array();
        $param["table"] = "notice_file";
        $param["col"]["notice_seqno"] = $fb->form("notice_seq");
        $param["col"]["origin_file_name"] = $_FILES["upload_btn"]["name"];
        $param["col"]["save_file_name"] = $result["save_file_name"];
        $param["col"]["file_path"] = $result["file_path"];

        $result = $bulletinDAO->insertData($conn,$param);
        if (!$result) $check = 0;
    }

//공지사항 추가
} else {

    $param["col"]["regi_date"] = date("Y-m-d H:i:s", time());
    $param["col"]["empl_seqno"] = $_SESSION["empl_seqno"];

    $result = $bulletinDAO->insertData($conn, $param);
    if (!$result) $check = 0;

    $notice_seqno = $conn->insert_ID();

    if ($fb->form("upload_file")) {

        //파일 업로드 경로
        $param =  array();
        $param["file_path"] = SITE_DEFAULT_NOTICE_FILE;
        $param["tmp_name"] = $_FILES["upload_btn"]["tmp_name"];
        $param["origin_file_name"] = $_FILES["upload_btn"]["name"];

        //파일을 업로드 한 후 저장된 경로를 리턴한다.
        $result= $fileDAO->upLoadFile($param);

        //공지사항 파일 추가
        $param = array();
        $param["table"] = "notice_file";
        $param["col"]["origin_file_name"] = $_FILES["upload_btn"]["name"];
        $param["col"]["save_file_name"] = $result["save_file_name"];
        $param["col"]["file_path"] = $result["file_path"];
        $param["col"]["notice_seqno"] = $notice_seqno;

        $result = $bulletinDAO->insertData($conn, $param);
        if (!$result) $check = 0;
    }
}

echo $check;

$conn->CompleteTrans();
$conn->close();
?>
