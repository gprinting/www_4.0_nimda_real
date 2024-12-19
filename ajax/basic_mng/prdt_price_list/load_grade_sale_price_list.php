<?
define("INC_PATH", $_SERVER["INC"]);
/*
 * Copyright (c) 2016 Nexmotion, Inc.
 * All rights reserved.
 * 
 * REVISION HISTORY (reverse chronological order)
 *============================================================================
 * 2016/10/21 엄준현 생성
 * 2016/12/01 엄준현 수정(쿼리 order by 추가)
 *============================================================================
 *
 */
include_once(INC_PATH . "/com/nexmotion/common/entity/FormBean.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/ConnectionPool.inc");
include_once(INC_PATH . "/com/nexmotion/common/util/nimda/ErpCommonUtil.inc");
include_once(INC_PATH . '/com/nexmotion/common/util/nimda/PriceHtmlLib.inc');
include_once(INC_PATH . '/com/nexmotion/job/nimda/basic_mng/prdt_price_mng/PrdtPriceListDAO.inc');

$connectionPool = new ConnectionPool();
$conn = $connectionPool->getPooledConnection();

$fb = new FormBean();
$dao = new PrdtPriceListDAO();
$util = new ErpCommonUtil();
$htmlLib = new PriceHtmlLib();

$fb = $fb->getForm();

$cate_sortcode = $fb["cate_sortcode"];
$sell_site     = $fb["sell_site"];
$mono_yn       = intval($fb["mono_yn"]);
$etprs_dvs     = $fb["etprs_dvs"];
$tmpt_dvs      = $fb["tmpt_dvs"];
$min_amt       = $fb["min_amt"];
$max_amt       = $fb["max_amt"];
$tax_yn        = $fb["tax_yn"];

$conn->debug = 1;

$param = array();

//* 판매채널에 해당하는 계산형 가격테이블 검색
$table_name = "ply_price";

//* 카테고리에 해당하는 수량단위 검색
$amt_unit = $dao->selectCateAmtUnit($conn, $cate_sortcode);

//* 종이 정보가 넘어온 것이 있을 경우 종이 맵핑코드 검색
$paper_info_arr = null;
$param["cate_sortcode"] = $cate_sortcode;
if (empty($fb["paper_name"]) === false) {
    // 종이 정보가 있을경우 파라미터 추가
    $param["name"]        = $fb["paper_name"];
    $param["dvs"]         = $fb["paper_dvs"];
    $param["color"]       = $fb["paper_color"];
    $param["basisweight"] = $fb["paper_basisweight"];
}
$paper_rs = $dao->selectPaperInfoAll($conn, $param);

$paper_total_arr = makePaperTotalInfoArr($paper_rs);
$paper_mpcode_arr = $paper_total_arr["mpcode"];
$paper_affil_arr  = $paper_total_arr["affil"];
$paper_info_arr   = $paper_total_arr["info"];

unset($paper_rs);
unset($paper_total_arr);
unset($param);

//* 인쇄도수가 선택된 경우 인쇄방식 맵핑코드 검색
$bef_print_tmpt = $fb["bef_print_tmpt"];
$bef_print_mpcode = null;
if (empty($bef_print_tmpt) === false) {
    $param["cate_sortcode"] = $cate_sortcode;
    $param["tmpt"]          = $bef_print_tmpt;

    // 전/후면 도수일 때만 처리
    if ($tmpt_dvs === '1') {
        $param["side_dvs"] = "전면";
    }

    $print_rs = $dao->selectCatePrintMpcode($conn, $param);

    $mpcode_arr = $util->rs2arr($print_rs, "mpcode");
    $mpcode_arr = $dao->parameterArrayEscape($conn, $mpcode_arr);
    $print_mpcode = $util->arr2delimStr($mpcode_arr);

    unset($mpcode_arr);
    unset($print_rs);

    $bef_print_mpcode = $print_mpcode;
}

$aft_print_tmpt = $fb["aft_print_tmpt"];
$aft_print_mpcode = null;
if (empty($aft_print_tmpt) === false) {
    $param["cate_sortcode"] = $cate_sortcode;
    $param["tmpt"]          = $aft_print_tmpt;
    $param["side_dvs"]      = "후면";

    $print_rs = $dao->selectCatePrintMpcode($conn, $param);

    $mpcode_arr = $util->rs2arr($print_rs, "mpcode");
    $mpcode_arr = $dao->parameterArrayEscape($conn, $mpcode_arr);
    $print_mpcode = $util->arr2delimStr($mpcode_arr);

    unset($mpcode_arr);
    unset($print_rs);

    $aft_print_mpcode = $print_mpcode;
}

unset($param);

$param["etprs_dvs"]        = $etprs_dvs;
$param["cate_sortcode"]    = $cate_sortcode;
$param["bef_print_mpcode"] = $bef_print_mpcode;
$param["aft_print_mpcode"] = $aft_print_mpcode;
$param["stan_mpcode"]      = $fb["output_size"];

//* 종이 맵핑코드 수 만큼 가격 검색
$paper_mpcode_arr_count = count($paper_mpcode_arr);

if ($paper_mpcode_arr_count === 0) {
    goto NOT_PRICE;
}

// 회원등급별 할인 정보 검색
$param["cate_sortcode"] = $cate_sortcode;
$param["etprs_dvs"]     = $etprs_dvs;

$grade_sale_arr = array();
$grade_sale_rs = $dao->selectMemberGradeRangeSale($conn, $param);

while ($grade_sale_rs && !$grade_sale_rs->EOF) {
    $fields = $grade_sale_rs->fields;

    $grade_sale_arr[$fields["grade"]] = doubleval($fields["rate"]) * -1;

    $grade_sale_rs->MoveNext();
}
unset($grade_sale_rs);

// 정보를 저장할 배열들
$amt_arr        = array(); // 수량 배열
$title_arr      = array(); // 제목 배열
$title_info_arr = array(); // 제목 정보 배열
$price_arr      = array(); // 가격 배열

// 확정형 가격일 때는 종이 계열 관계없이 종류만 따짐
$dup_chk = array();

$param["min_amt"] = $min_amt;
$param["max_amt"] = $max_amt;

for ($i = 0; $i < $paper_mpcode_arr_count; $i++) {
    $paper_mpcode = $paper_mpcode_arr[$i];
    $paper_affil  = $paper_affil_arr[$i];
    $paper_info   = $paper_info_arr[$i];

    $param["paper_mpcode"] = $paper_mpcode;

    $price_info_rs = null;
    if ($mono_yn === 0) {
        if ($dup_chk[$paper_info] === null) {
           $dup_chk[$paper_info] = true;
        } else {
            continue;
        }
        $price_info_rs = $dao->selectCatePriceList($conn,
                                                   $table_name,
                                                   $param,
                                                   $tmpt_dvs);
    } else {
        /* 계산형 가격테이블 없음
        $param["affil"] = $paper_affil;
        $price_info_rs = $dao->selectCateCalcPriceList($conn,
                                                       $table_name,
                                                       $param,
                                                       $tmpt_dvs);
                                                       */

    }

    if ($price_info_rs->EOF === true) {
        continue;
    }

    $param["amt_unit"]   = $amt_unit;

    if ($mono_yn === 0) {
        $param["paper_info"] = $paper_info;

        makePlyInfoArr($price_info_rs,
                       $amt_arr,
                       $title_arr,
                       $title_info_arr,
                       $price_arr,
                       $grade_sale_arr,
                       $param,
                       $util);
    } else {
        $param["paper_info"] = $paper_affil . ' ' . $paper_info;

        makeCalcInfoArr($price_info_rs,
                        $amt_arr,
                        $title_arr,
                        $price_arr,
                        $grade_sale_arr,
                        $param);
    }
}

if (count($amt_arr) === 0) {
    goto NOT_PRICE;
}

//* 생성된 정보배열 인덱스 정수로 바꿔서 정렬
// 수량배열 정렬
$amt_arr = $util->sortDvsArr($amt_arr);

// 제목배열, 제목 정보배열, 가격배열 정렬
$title_arr_sort      = array();
$price_arr_sort      = array();

$i = 0;
foreach ($title_arr as $key => $val) {
    $title          = $val;
    $title_info     = $title_info_arr[$key];
    $price_arr_temp = $price_arr[$key];

    $title_arr_sort[$i]      = $title; // 종이정보
    $price_arr_sort[$i++]    = $price_arr_temp; // 가격정보
}

unset($title_arr);
unset($price_arr);

$title_id_arr = array(
    0 => "-",
    1 => "-",
    2 => "-",
    3 => "-",
    4 => "-",
    5 => "-",
    6 => "-",
    7 => "-",
    8 => "-"
);

/*
print_r($title_arr_sort);
print_r($price_arr_sort);
exit;
*/

if ($etprs_dvs === "new") {
    $type = 6;
} else {
    $type = 7;
}

$htmlLib->initInfo(count($title_id_arr), $type, "수량");

$thead = $htmlLib->getGradeSalePriceTheadHtml($title_arr_sort,
                                              $grade_sale_arr,
                                              $etprs_dvs);

if ($mono_yn === 0) {
    $tbody = $htmlLib->getGradeSalePriceTbodyHtml(count($title_arr_sort),
                                                  $price_arr_sort,
                                                  $amt_arr,
                                                  $etprs_dvs,
                                                  $tax_yn);
} else {
    /*
    $tbody = $htmlLib->getCalcPriceTbodyHtml(count($title_arr_sort),
                                             $price_arr_sort,
                                             $amt_arr);
                                             */
}

$colgroup = $htmlLib->getColgroupHtml();

echo $colgroup . $thead . $tbody;

$conn->Close();
exit;

NOT_PRICE :
    $conn->Close();
    echo "<tr><td>검색된 내용이 없습니다.</td></tr>";
    exit;

/******************************************************************************
                            함수 영역
 *****************************************************************************/

/**
 * @brief 종이 정보 검색결과를 가격검색 및 제목생성에
 * 사용할 수 있도록 가공하는 함수
 *
 * @param $rs = 검색결과
 *
 * @return 가공된 배열
 */
function makePaperTotalInfoArr($rs) {
    $ret = array(
        "mpcode" => array(), // 종이 맵핑코드 배열, 가격검색시 사용
        "info"   => array()  // 종이 정보 배열, 제목 생성시 사용
    );

    $info_form = "%s %s %s %s";

    $i = 0;
    while ($rs && !$rs->EOF) {
        $mpcode      = $rs->fields["mpcode"];

        $name        = $rs->fields["name"];
        $dvs         = $rs->fields["dvs"];
        $color       = $rs->fields["color"];
        $basisweight = $rs->fields["basisweight"];
        $affil       = $rs->fields["affil"];

        $ret["mpcode"][$i] = $mpcode;
        $ret["affil"][$i]  = $affil;
        $ret["info"][$i++] = sprintf($info_form, $name
                                               , $dvs
                                               , $color
                                               , $basisweight);
        $rs->MoveNext();
    }

    return $ret;
}

/**
 * @brief 가격정보 검색결과를 확정형 가격에 맞춰서 정보배열 생성
 *
 * @param &$rs = 검색결과
 * @param &$amt_arr        = 수량 배열
 * @param &$title_arr      = 제목 배열
 * @param &$title_info_arr = 제목 정보 배열
 * @param &$price_arr      = 가격 배열
 * @param $param           = $title, $title_mpcode 생성용
 *                           종이정보, 기준수량, 맵핑코드들
 * @param $util            = util 객체
 *
 * @return 가공된 배열
 */
function makePlyInfoArr(&$rs,
                        &$amt_arr,
                        &$title_arr,
                        &$title_info_arr,
                        &$price_arr,
                        &$grade_sale_arr,
                        $param,
                        $util) {
    // 카테고리명|종이정보|사이즈 사이즈유형|페이지구분 페이지수 페이지상세|전면인쇄도수|전면추가인쇄도수|후면인쇄도수|후면추가인쇄도수|기준수량
    $title_form = "%s|%s|%s %s|%s %sp %s|%s|%s|%s|%s|%s";

    $cate_sortcode    = $param["cate_sortcode"];
    $paper_mpcode     = $param["paper_mpcode"];
    $paper_info       = $param["paper_info"];
    $amt_unit         = $param["amt_unit"];
    $etprs_dvs        = $param["etprs_dvs"];

    if ($etprs_dvs === "new") {
        $price_form = "%s|%s|%s|%s|%s";
    } else {
        $price_form = "%s|%s|%s|%s|%s|%s|%s|%s|%s";
    }

    while ($rs && !$rs->EOF) {
        $fields = $rs->fields;

        $price_seqno = $fields["price_seqno"];
        $amt         = $fields["amt"];
        $new_price   = $fields["new_price"];

        $cate_name = $fields["cate_name"];

        $page        = $fields["page"];
        $page_dvs    = $fields["page_dvs"];
        $page_detail = $fields["page_detail"];

        $bef_print_name     = $fields["bef_print_name"];
        $bef_add_print_name = $fields["bef_add_print_name"];
        $aft_print_name     = $fields["aft_print_name"];
        $aft_add_print_name = $fields["aft_add_print_name"];

        $stan_name = $fields["stan_name"];
        $stan_typ  = $fields["stan_typ"];

        if (empty($bef_add_print_name) === true) {
            $bef_add_print_name = '-';
        }
        if (empty($aft_print_name) === true) {
            $aft_print_name = '-';
        }
        if (empty($aft_add_print_name) === true) {
            $aft_add_print_name = '-';
        }

        $title = sprintf($title_form, $cate_name
                                    , $paper_info
                                    , $stan_name
                                    , $stan_typ
                                    , $page_dvs
                                    , $page
                                    , $page_detail
                                    , $bef_print_name
                                    , $bef_add_print_name
                                    , $aft_print_name
                                    , $aft_add_print_name
                                    , $amt_unit);

        if ($etprs_dvs === "new") {
            $grade_10 = $util->calcPrice($grade_sale_arr[10], $new_price);
            $grade_9  = $util->calcPrice($grade_sale_arr[9], $new_price);
            $grade_8  = $util->calcPrice($grade_sale_arr[8], $new_price);

            $price = sprintf($price_form, $price_seqno
                                        , $new_price
                                        , $new_price + $grade_10
                                        , $new_price + $grade_9
                                        , $new_price + $grade_8);
        } else {
            $grade_7 = $util->calcPrice($grade_sale_arr[7], $new_price);
            $grade_6 = $util->calcPrice($grade_sale_arr[6], $new_price);
            $grade_5 = $util->calcPrice($grade_sale_arr[5], $new_price);
            $grade_4 = $util->calcPrice($grade_sale_arr[4], $new_price);
            $grade_3 = $util->calcPrice($grade_sale_arr[3], $new_price);
            $grade_2 = $util->calcPrice($grade_sale_arr[2], $new_price);
            $grade_1 = $util->calcPrice($grade_sale_arr[1], $new_price);

            $price = sprintf($price_form, $price_seqno
                                        , $new_price
                                        , $new_price + $grade_7
                                        , $new_price + $grade_6
                                        , $new_price + $grade_5
                                        , $new_price + $grade_4
                                        , $new_price + $grade_3
                                        , $new_price + $grade_2
                                        , $new_price + $grade_1);
        }

        $title_arr[$title] = $title;
        $amt_arr[$amt] = $amt;
        $price_arr[$title][$amt] = $price;

        $rs->MoveNext();
    }
}

/**
 * @brief 가격정보 검색결과를 계산형 가격에 맞춰서 정보배열 생성
 *
 * @param &$rs = 검색결과
 * @param &$amt_arr   = 수량 배열
 * @param &$title_arr = 제목 배열
 * @param &$price_arr = 가격 배열
 * @param $param      = $title 생성용
 *                      종이정보, 기준수량
 *
 * @return 가공된 배열
 */
function makeCalcInfoArr(&$rs,
                         &$amt_arr,
                         &$title_arr,
                         &$price_arr,
                         $param) {
    // 각 정보항목 폼
    // 카테고리명|종이정보|사이즈 사이즈유형|페이지구분 페이지수 페이지상세|전면인쇄도수|전면추가인쇄도수|후면인쇄도수|후면추가인쇄도수|기준수량
    $title_form = "%s|%s|%s %s|%s %sp %s|%s|%s|%s|%s|%s|%s";
    // 일련번호|종이금액|인쇄금액|출력금액|총합금액
    $price_form = "%s|%s|%s|%s|%s";

    $paper_info = $param["paper_info"];
    $amt_unit   = $param["amt_unit"];

    while ($rs && !$rs->EOF) {
        $price_seqno  = $rs->fields["price_seqno"];
        $affil        = $rs->fields["affil"];
        $amt          = $rs->fields["amt"];
        $paper_price  = $rs->fields["paper_price"];
        $print_price  = $rs->fields["print_price"];
        $output_price = $rs->fields["output_price"];
        $sum_price    = $rs->fields["sum_price"];

        $cate_name = $rs->fields["cate_name"];

        $page        = $rs->fields["page"];
        $page_dvs    = $rs->fields["page_dvs"];
        $page_detail = $rs->fields["page_detail"];

        $bef_print_name = $rs->fields["bef_print_name"];
        $aft_print_name = $rs->fields["aft_print_name"];
        $bef_add_print_name = $rs->fields["bef_add_print_name"];
        $aft_add_print_name = $rs->fields["aft_add_print_name"];

        $stan_name = $rs->fields["stan_name"];
        $stan_typ  = $rs->fields["stan_typ"];

        if (empty($bef_add_print_name) === true) {
            $bef_add_print_name = '-';
        }
        if (empty($aft_print_name) === true) {
            $aft_print_name = '-';
        }
        if (empty($aft_add_print_name) === true) {
            $aft_add_print_name = '-';
        }

        $title = sprintf($title_form, $cate_name
                                    , $paper_info
                                    , $stan_name
                                    , $stan_typ
                                    , $page_dvs
                                    , $page
                                    , $page_detail
                                    , $bef_print_name
                                    , $bef_add_print_name
                                    , $aft_print_name
                                    , $aft_add_print_name
                                    , $amt_unit);

        $price = sprintf($price_form, $price_seqno
                                    , $paper_price
                                    , $print_price
                                    , $output_price
                                    , $sum_price);

        $amt_arr[$amt] = $amt;
        $title_arr[$title] = $title;
        $price_arr[$title][$amt] = $price;

        $rs->MoveNext();
    }
}
?>
