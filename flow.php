<?php

/**
 * ECSHOP 璐?墿娴佺▼
 * ============================================================================
 * 鐗堟潈鎵€鏈 2005-2010 涓婃捣鍟嗘淳缃戠粶绉戞妧鏈夐檺鍏?徃锛屽苟淇濈暀鎵€鏈夋潈鍒┿€
 * 缃戠珯鍦板潃: http://www.ecshop.com锛
 * ----------------------------------------------------------------------------
 * 杩欎笉鏄?竴涓?嚜鐢辫蒋浠讹紒鎮ㄥ彧鑳藉湪涓嶇敤浜庡晢涓氱洰鐨勭殑鍓嶆彁涓嬪?绋嬪簭浠ｇ爜杩涜?淇?敼鍜
 * 浣跨敤锛涗笉鍏佽?瀵圭▼搴忎唬鐮佷互浠讳綍褰㈠紡浠讳綍鐩?殑鐨勫啀鍙戝竷銆
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: flow.php 17218 2011-01-24 04:10:41Z yehuaixiao $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');

/* 杞藉叆璇?█鏂囦欢 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

if (!isset($_REQUEST['step']))
{
    $_REQUEST['step'] = "cart";
}

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

assign_template();
assign_dynamic('flow');
$position = assign_ur_here(0, $_LANG['shopping_flow']);
$smarty->assign('page_title',       $position['title']);    // 椤甸潰鏍囬?
$smarty->assign('ur_here',          $position['ur_here']);  // 褰撳墠浣嶇疆

$smarty->assign('categories',       get_categories_tree()); // 鍒嗙被鏍
$smarty->assign('helps',            get_shop_help());       // 缃戝簵甯?姪
$smarty->assign('lang',             $_LANG);
$smarty->assign('show_marketprice', $_CFG['show_marketprice']);
$smarty->assign('data_dir',    DATA_DIR);       // 鏁版嵁鐩?綍

/*------------------------------------------------------ */
//-- 娣诲姞鍟嗗搧鍒拌喘鐗╄溅
/*------------------------------------------------------ */
if ($_REQUEST['step'] == 'add_to_cart')
{
    include_once('includes/cls_json.php');
    $_POST['goods']=strip_tags(urldecode($_POST['goods']));
    $_POST['goods'] = json_str_iconv($_POST['goods']);

    if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))
    {
        if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)
        {
            ecs_header("Location:./\n");
        }
        $goods_id = intval($_REQUEST['goods_id']);
        exit;
    }

    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
    $json  = new JSON;

    if (empty($_POST['goods']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $goods = $json->decode($_POST['goods']);

    /* 妫€鏌ワ細濡傛灉鍟嗗搧鏈夎?鏍硷紝鑰宲ost鐨勬暟鎹?病鏈夎?鏍硷紝鎶婂晢鍝佺殑瑙勬牸灞炴€ч€氳繃JSON浼犲埌鍓嶅彴 */
    if (empty($goods->spec) AND empty($goods->quick))
    {
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".
            "g.goods_attr_id, g.attr_value, g.attr_price " .
        'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
        "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .
        'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';

        $res = $GLOBALS['db']->getAll($sql);

        if (!empty($res))
        {
            $spe_arr = array();
            foreach ($res AS $row)
            {
                $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];
                $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];
                $spe_arr[$row['attr_id']]['values'][] = array(
                                                            'label'        => $row['attr_value'],
                                                            'price'        => $row['attr_price'],
                                                            'format_price' => price_format($row['attr_price'], false),
                                                            'id'           => $row['goods_attr_id']);
            }
            $i = 0;
            $spe_array = array();
            foreach ($spe_arr AS $row)
            {
                $spe_array[]=$row;
            }
            $result['error']   = ERR_NEED_SELECT_ATTR;
            $result['goods_id'] = $goods->goods_id;
            $result['parent'] = $goods->parent;
            $result['message'] = $spe_array;

            die($json->encode($result));
        }
    }

    /* 鏇存柊锛氬?鏋滄槸涓€姝ヨ喘鐗╋紝鍏堟竻绌鸿喘鐗╄溅 */
    if ($_CFG['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 妫€鏌ワ細鍟嗗搧鏁伴噺鏄?惁鍚堟硶 */
    if (!is_numeric($goods->number) || intval($goods->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    /* 鏇存柊锛氳喘鐗╄溅 */
    else
    {
        // 鏇存柊锛氭坊鍔犲埌璐?墿杞
        if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent))
        {
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }

            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_CFG['one_step_buy'];
        }
        else
        {
            $result['message']  = $err->last_message();
            $result['error']    = $err->error_no;
            $result['goods_id'] = stripslashes($goods->goods_id);
            if (is_array($goods->spec))
            {
                $result['product_spec'] = implode(',', $goods->spec);
            }
            else
            {
                $result['product_spec'] = $goods->spec;
            }
        }
    }

    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'link_buy')
{
    $goods_id = intval($_GET['goods_id']);

    if (!cart_goods_exists($goods_id,array()))
    {
        addto_cart($goods_id);
    }
    ecs_header("Location:./flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'login')
{
    include_once('languages/'. $_CFG['lang']. '/user.php');

    /*
     * 鐢ㄦ埛鐧诲綍娉ㄥ唽
     */
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        $smarty->assign('anonymous_buy', $_CFG['anonymous_buy']);

        /* 妫€鏌ユ槸鍚︽湁璧犲搧锛屽?鏋滄湁鎻愮ず鐧诲綍鍚庨噸鏂伴€夋嫨璧犲搧 */
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' AND is_gift > 0";
        if ($db->getOne($sql) > 0)
        {
            $smarty->assign('need_rechoose_gift', 1);
        }

        /* 妫€鏌ユ槸鍚﹂渶瑕佹敞鍐岀爜 */
        $captcha = intval($_CFG['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $smarty->assign('enabled_login_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
        if ($captcha & CAPTCHA_REGISTER)
        {
            $smarty->assign('enabled_register_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
    }
    else
    {
        include_once('includes/lib_passport.php');
        if (!empty($_POST['act']) && $_POST['act'] == 'signin')
        {
            $captcha = intval($_CFG['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 妫€鏌ラ獙璇佺爜 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            if ($user->login($_POST['username'], $_POST['password'],isset($_POST['remember'])))
            {
                update_user_info();  //鏇存柊鐢ㄦ埛淇℃伅
                recalculate_price(); // 閲嶆柊璁＄畻璐?墿杞︿腑鐨勫晢鍝佷环鏍

                /* 妫€鏌ヨ喘鐗╄溅涓?槸鍚︽湁鍟嗗搧 娌℃湁鍟嗗搧鍒欒烦杞?埌棣栭〉 */
                $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') . " WHERE session_id = '" . SESS_ID . "' ";
                if ($db->getOne($sql) > 0)
                {
                    ecs_header("Location: flow.php?step=checkout\n");
                }
                else
                {
                    ecs_header("Location:index.php\n");
                }

                exit;
            }
            else
            {
                $_SESSION['login_fail']++;
                show_message($_LANG['signin_failed'], '', 'flow.php?step=login');
            }
        }
        elseif (!empty($_POST['act']) && $_POST['act'] == 'signup')
        {
            if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 妫€鏌ラ獙璇佺爜 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            if (register(trim($_POST['username']), trim($_POST['password']), trim($_POST['email'])))
            {
                /* 鐢ㄦ埛娉ㄥ唽鎴愬姛 */
                ecs_header("Location: flow.php?step=consignee\n");
                exit;
            }
            else
            {
                $err->show();
            }
        }
        else
        {
            // TODO: 闈炴硶璁块棶鐨勫?鐞
        }
    }
}
elseif ($_REQUEST['step'] == 'consignee')
{
    /*------------------------------------------------------ */
    //-- 鏀惰揣浜轰俊鎭
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        /* 鍙栧緱璐?墿绫诲瀷 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /*
         * 鏀惰揣浜轰俊鎭?～鍐欑晫闈
         */

        if (isset($_REQUEST['direct_shopping']))
        {
            $_SESSION['direct_shopping'] = 1;
        }

        /* 鍙栧緱鍥藉?鍒楄〃銆佸晢搴楁墍鍦ㄥ浗瀹躲€佸晢搴楁墍鍦ㄥ浗瀹剁殑鐪佸垪琛 */
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_country',       $_CFG['shop_country']);
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

        /* 鑾峰緱鐢ㄦ埛鎵€鏈夌殑鏀惰揣浜轰俊鎭 */
        if ($_SESSION['user_id'] > 0)
        {
            $consignee_list = get_consignee_list($_SESSION['user_id']);

            if (count($consignee_list) < 5)
            {
                /* 濡傛灉鐢ㄦ埛鏀惰揣浜轰俊鎭?殑鎬绘暟灏忎簬 5 鍒欏?鍔犱竴涓?柊鐨勬敹璐т汉淇℃伅 */
                $consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
            }
        }
        else
        {
            if (isset($_SESSION['flow_consignee'])){
                $consignee_list = array($_SESSION['flow_consignee']);
            }
            else
            {
                $consignee_list[] = array('country' => $_CFG['shop_country']);
            }
        }
        $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));
        $smarty->assign('consignee_list', $consignee_list);

        /* 鍙栧緱姣忎釜鏀惰揣鍦板潃鐨勭渷甯傚尯鍒楄〃 */
        $province_list = array();
        $city_list = array();
        $district_list = array();
        foreach ($consignee_list as $region_id => $consignee)
        {
            $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
            $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
            $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

            $province_list[$region_id] = get_regions(1, $consignee['country']);
            $city_list[$region_id]     = get_regions(2, $consignee['province']);
            $district_list[$region_id] = get_regions(3, $consignee['city']);
        }
        $smarty->assign('province_list', $province_list);
        $smarty->assign('city_list',     $city_list);
        $smarty->assign('district_list', $district_list);

        /* 杩斿洖鏀惰揣浜洪〉闈?唬鐮 */
        $smarty->assign('real_goods_count', exist_real_goods(0, $flow_type) ? 1 : 0);
    }
    else
    {
        /*
         * 淇濆瓨鏀惰揣浜轰俊鎭
         */
        $consignee = array(
            'address_id'    => empty($_POST['address_id']) ? 0  : intval($_POST['address_id']),
            'consignee'     => empty($_POST['consignee'])  ? '' : trim($_POST['consignee']),
            'country'       => empty($_POST['country'])    ? '' : $_POST['country'],
            'province'      => empty($_POST['province'])   ? '' : $_POST['province'],
            'city'          => empty($_POST['city'])       ? '' : $_POST['city'],
            'district'      => empty($_POST['district'])   ? '' : $_POST['district'],
            'email'         => empty($_POST['email'])      ? '' : $_POST['email'],
            'address'       => empty($_POST['address'])    ? '' : $_POST['address'],
            'zipcode'       => empty($_POST['zipcode'])    ? '' : make_semiangle(trim($_POST['zipcode'])),
            'tel'           => empty($_POST['tel'])        ? '' : make_semiangle(trim($_POST['tel'])),
            'mobile'        => empty($_POST['mobile'])     ? '' : make_semiangle(trim($_POST['mobile'])),
            'sign_building' => empty($_POST['sign_building']) ? '' : $_POST['sign_building'],
            'best_time'     => empty($_POST['best_time'])  ? '' : $_POST['best_time'],
        );

        if ($_SESSION['user_id'] > 0)
        {
            include_once(ROOT_PATH . 'includes/lib_transaction.php');

            /* 濡傛灉鐢ㄦ埛宸茬粡鐧诲綍锛屽垯淇濆瓨鏀惰揣浜轰俊鎭 */
            $consignee['user_id'] = $_SESSION['user_id'];

            save_consignee($consignee, true);
        }

        /* 淇濆瓨鍒皊ession */
        $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }
}
elseif ($_REQUEST['step'] == 'drop_consignee')
{
    /*------------------------------------------------------ */
    //-- 鍒犻櫎鏀惰揣浜轰俊鎭
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }
    else
    {
        show_message($_LANG['not_fount_consignee']);
    }
}
elseif ($_REQUEST['step'] == 'checkout')
{
    /*------------------------------------------------------ */
    //-- 璁㈠崟纭??
    /*------------------------------------------------------ */

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鍥㈣喘鏍囧織 */
    if ($flow_type == CART_GROUP_BUY_GOODS)
    {
        $smarty->assign('is_group_buy', 1);
    }
    /* 绉?垎鍏戞崲鍟嗗搧 */
    elseif ($flow_type == CART_EXCHANGE_GOODS)
    {
        $smarty->assign('is_exchange_goods', 1);
    }
    else
    {
        //姝ｅ父璐?墿娴佺▼  娓呯┖鍏朵粬璐?墿娴佺▼鎯呭喌
        $_SESSION['flow_order']['extension_code'] = '';
    }

    /* 妫€鏌ヨ喘鐗╄溅涓?槸鍚︽湁鍟嗗搧 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
        " WHERE session_id = '" . SESS_ID . "' " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }

    /*
     * 妫€鏌ョ敤鎴锋槸鍚﹀凡缁忕櫥褰
     * 濡傛灉鐢ㄦ埛宸茬粡鐧诲綍浜嗗垯妫€鏌ユ槸鍚︽湁榛樿?鐨勬敹璐у湴鍧€
     * 濡傛灉娌℃湁鐧诲綍鍒欒烦杞?埌鐧诲綍鍜屾敞鍐岄〉闈
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 鐢ㄦ埛娌℃湁鐧诲綍涓旀病鏈夐€夊畾鍖垮悕璐?墿锛岃浆鍚戝埌鐧诲綍椤甸潰 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

    $consignee = get_consignee($_SESSION['user_id']);

    /* 妫€鏌ユ敹璐т汉淇℃伅鏄?惁瀹屾暣 */
    if (!check_consignee_info($consignee, $flow_type))
    {
        /* 濡傛灉涓嶅畬鏁村垯杞?悜鍒版敹璐т汉淇℃伅濉?啓鐣岄潰 */
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }

    $_SESSION['flow_consignee'] = $consignee;
    $smarty->assign('consignee', $consignee);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁
    $smarty->assign('goods_list', $cart_goods);

    /* 瀵规槸鍚﹀厑璁镐慨鏀硅喘鐗╄溅璧嬪€ */
    if ($flow_type != CART_GENERAL_GOODS || $_CFG['one_step_buy'] == '1')
    {
        $smarty->assign('allow_edit_cart', 0);
    }
    else
    {
        $smarty->assign('allow_edit_cart', 1);
    }

    /*
     * 鍙栧緱璐?墿娴佺▼璁剧疆
     */
    $smarty->assign('config', $_CFG);
    /*
     * 鍙栧緱璁㈠崟淇℃伅
     */
    $order = flow_order_info();
    $smarty->assign('order', $order);

    /* 璁＄畻鎶樻墸 */
    if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS)
    {
        $discount = compute_discount();
        $smarty->assign('discount', $discount['discount']);
        $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
        $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));
    }

    /*
     * 璁＄畻璁㈠崟鐨勮垂鐢
     */
    $total = order_fee($order, $cart_goods, $consignee);

    $smarty->assign('total', $total);
    $smarty->assign('shopping_money', sprintf($_LANG['shopping_money'], $total['formated_goods_price']));
    $smarty->assign('market_price_desc', sprintf($_LANG['than_market_price'], $total['formated_market_price'], $total['formated_saving'], $total['save_rate']));

    /* 鍙栧緱閰嶉€佸垪琛 */
    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
    $shipping_list     = available_shipping_list($region);
    $cart_weight_price = cart_weight_price($flow_type);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 鏌ョ湅璐?墿杞︿腑鏄?惁鍏ㄤ负鍏嶈繍璐瑰晢鍝侊紝鑻ユ槸鍒欐妸杩愯垂璧嬩负闆
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);

    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        /* 褰撳墠鐨勯厤閫佹柟寮忔槸鍚︽敮鎸佷繚浠 */
        if ($val['shipping_id'] == $order['shipping_id'])
        {
            $insure_disabled = ($val['insure'] == 0);
            $cod_disabled    = ($val['support_cod'] == 0);
        }
    }

    $smarty->assign('shipping_list',   $shipping_list);
    $smarty->assign('insure_disabled', $insure_disabled);
    $smarty->assign('cod_disabled',    $cod_disabled);

    /* 鍙栧緱鏀?粯鍒楄〃 */
    if ($order['shipping_id'] == 0)
    {
        $cod        = true;
        $cod_fee    = 0;
    }
    else
    {
        $shipping = shipping_info($order['shipping_id']);
        $cod = $shipping['support_cod'];

        if ($cod)
        {
            /* 濡傛灉鏄?洟璐?紝涓斾繚璇侀噾澶т簬0锛屼笉鑳戒娇鐢ㄨ揣鍒颁粯娆 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $group_buy_id = $_SESSION['extension_id'];
                if ($group_buy_id <= 0)
                {
                    show_message('error group_buy_id');
                }
                $group_buy = group_buy_info($group_buy_id);
                if (empty($group_buy))
                {
                    show_message('group buy not exists: ' . $group_buy_id);
                }

                if ($group_buy['deposit'] > 0)
                {
                    $cod = false;
                    $cod_fee = 0;

                    /* 璧嬪€间繚璇侀噾 */
                    $smarty->assign('gb_deposit', $group_buy['deposit']);
                }
            }

            if ($cod)
            {
                $shipping_area_info = shipping_area_info($order['shipping_id'], $region);
                $cod_fee            = $shipping_area_info['pay_fee'];
            }
        }
        else
        {
            $cod_fee = 0;
        }
    }

    // 缁欒揣鍒颁粯娆剧殑鎵嬬画璐瑰姞<span id>锛屼互渚挎敼鍙橀厤閫佺殑鏃跺€欏姩鎬佹樉绀
    $payment_list = available_payment_list(1, $cod_fee);
    if(isset($payment_list))
    {
        foreach ($payment_list as $key => $payment)
        {
            if ($payment['is_cod'] == '1')
            {
                $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
            }
            /* 濡傛灉鏈夋槗瀹濈?宸炶?鏀?粯 濡傛灉璁㈠崟閲戦?澶т簬300 鍒欎笉鏄剧ず */
            if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300)
            {
                unset($payment_list[$key]);
            }
            /* 濡傛灉鏈変綑棰濇敮浠 */
            if ($payment['pay_code'] == 'balance')
            {
                /* 濡傛灉鏈?櫥褰曪紝涓嶆樉绀 */
                if ($_SESSION['user_id'] == 0)
                {
                    unset($payment_list[$key]);
                }
                else
                {
                    if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id'])
                    {
                        $smarty->assign('disable_surplus', 1);
                    }
                }
            }
        }
    }
    $smarty->assign('payment_list', $payment_list);

    /* 鍙栧緱鍖呰?涓庤春鍗 */
    if ($total['real_goods_count'] > 0)
    {
        /* 鍙?湁鏈夊疄浣撳晢鍝?鎵嶈?鍒ゆ柇鍖呰?鍜岃春鍗 */
        if (!isset($_CFG['use_package']) || $_CFG['use_package'] == '1')
        {
            /* 濡傛灉浣跨敤鍖呰?锛屽彇寰楀寘瑁呭垪琛ㄥ強鐢ㄦ埛閫夋嫨鐨勫寘瑁 */
            $smarty->assign('pack_list', pack_list());
        }

        /* 濡傛灉浣跨敤璐哄崱锛屽彇寰楄春鍗″垪琛ㄥ強鐢ㄦ埛閫夋嫨鐨勮春鍗 */
        if (!isset($_CFG['use_card']) || $_CFG['use_card'] == '1')
        {
            $smarty->assign('card_list', card_list());
        }
    }

    $user_info = user_info($_SESSION['user_id']);

    /* 濡傛灉浣跨敤浣欓?锛屽彇寰楃敤鎴蜂綑棰 */
    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1')
        && $_SESSION['user_id'] > 0
        && $user_info['user_money'] > 0)
    {
        // 鑳戒娇鐢ㄤ綑棰
        $smarty->assign('allow_use_surplus', 1);
        $smarty->assign('your_surplus', $user_info['user_money']);
    }

    /* 濡傛灉浣跨敤绉?垎锛屽彇寰楃敤鎴峰彲鐢ㄧН鍒嗗強鏈??鍗曟渶澶氬彲浠ヤ娇鐢ㄧ殑绉?垎 */
    if ((!isset($_CFG['use_integral']) || $_CFG['use_integral'] == '1')
        && $_SESSION['user_id'] > 0
        && $user_info['pay_points'] > 0
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
    {
        // 鑳戒娇鐢ㄧН鍒
        $smarty->assign('allow_use_integral', 1);
        $smarty->assign('order_max_integral', flow_available_points());  // 鍙?敤绉?垎
        $smarty->assign('your_integral',      $user_info['pay_points']); // 鐢ㄦ埛绉?垎
    }

    /* 濡傛灉浣跨敤绾㈠寘锛屽彇寰楃敤鎴峰彲浠ヤ娇鐢ㄧ殑绾㈠寘鍙婄敤鎴烽€夋嫨鐨勭孩鍖 */
    if ((!isset($_CFG['use_bonus']) || $_CFG['use_bonus'] == '1')
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
    {
        // 鍙栧緱鐢ㄦ埛鍙?敤绾㈠寘
        $user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price']);
        if (!empty($user_bonus))
        {
            foreach ($user_bonus AS $key => $val)
            {
                $user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
            }
            $smarty->assign('bonus_list', $user_bonus);
        }

        // 鑳戒娇鐢ㄧ孩鍖
        $smarty->assign('allow_use_bonus', 1);
    }

    /* 濡傛灉浣跨敤缂鸿揣澶勭悊锛屽彇寰楃己璐у?鐞嗗垪琛 */
    if (!isset($_CFG['use_how_oos']) || $_CFG['use_how_oos'] == '1')
    {
        if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos']))
        {
            $smarty->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
        }
    }

    /* 濡傛灉鑳藉紑鍙戠エ锛屽彇寰楀彂绁ㄥ唴瀹瑰垪琛 */
    if ((!isset($_CFG['can_invoice']) || $_CFG['can_invoice'] == '1')
        && isset($_CFG['invoice_content'])
        && trim($_CFG['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS)
    {
        $inv_content_list = explode("\n", str_replace("\r", '', $_CFG['invoice_content']));
        $smarty->assign('inv_content_list', $inv_content_list);

        $inv_type_list = array();
        foreach ($_CFG['invoice_type']['type'] as $key => $type)
        {
            if (!empty($type))
            {
                $inv_type_list[$type] = $type . ' [' . floatval($_CFG['invoice_type']['rate'][$key]) . '%]';
            }
        }
        $smarty->assign('inv_type_list', $inv_type_list);
    }

    /* 淇濆瓨 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'select_shipping')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉閰嶉€佹柟寮
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $order['shipping_id'] = intval($_REQUEST['shipping']);
        $regions = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
        $shipping_info = shipping_area_info($order['shipping_id'], $regions);

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍙栧緱鍙?互寰楀埌鐨勭Н鍒嗗拰绾㈠寘 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['cod_fee']     = $shipping_info['pay_fee'];
        if (strpos($result['cod_fee'], '%') === false)
        {
            $result['cod_fee'] = price_format($result['cod_fee'], false);
        }
        $result['need_insure'] = ($shipping_info['insure'] > 0 && !empty($order['need_insure'])) ? 1 : 0;
        $result['content']     = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_insure')
{
    /*------------------------------------------------------ */
    //-- 閫夊畾/鍙栨秷閰嶉€佺殑淇濅环
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $order['need_insure'] = intval($_REQUEST['insure']);

        /* 淇濆瓨 session */
        $_SESSION['flow_order'] = $order;

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍙栧緱鍙?互寰楀埌鐨勭Н鍒嗗拰绾㈠寘 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_payment')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉鏀?粯鏂瑰紡
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $order['pay_id'] = intval($_REQUEST['payment']);
        $payment_info = payment_info($order['pay_id']);
        $result['pay_code'] = $payment_info['pay_code'];

        /* 淇濆瓨 session */
        $_SESSION['flow_order'] = $order;

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍙栧緱鍙?互寰楀埌鐨勭Н鍒嗗拰绾㈠寘 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_pack')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉鍟嗗搧鍖呰?
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $order['pack_id'] = intval($_REQUEST['pack']);

        /* 淇濆瓨 session */
        $_SESSION['flow_order'] = $order;

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍙栧緱鍙?互寰楀埌鐨勭Н鍒嗗拰绾㈠寘 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_card')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉璐哄崱
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $order['card_id'] = intval($_REQUEST['card']);

        /* 淇濆瓨 session */
        $_SESSION['flow_order'] = $order;

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍙栧緱鍙?互寰楀埌鐨勭Н鍒嗗拰绾㈠寘 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $order['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'change_surplus')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉浣欓?
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $surplus   = floatval($_GET['surplus']);
    $user_info = user_info($_SESSION['user_id']);

    if ($user_info['user_money'] + $user_info['credit_line'] < $surplus)
    {
        $result['error'] = $_LANG['surplus_not_enough'];
    }
    else
    {
        /* 鍙栧緱璐?墿绫诲瀷 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鑾峰緱鏀惰揣浜轰俊鎭 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
        $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

        if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
        {
            $result['error'] = $_LANG['no_goods_in_cart'];
        }
        else
        {
            /* 鍙栧緱璁㈠崟淇℃伅 */
            $order = flow_order_info();
            $order['surplus'] = $surplus;

            /* 璁＄畻璁㈠崟鐨勮垂鐢 */
            $total = order_fee($order, $cart_goods, $consignee);
            $smarty->assign('total', $total);

            /* 鍥㈣喘鏍囧織 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

            $result['content'] = $smarty->fetch('library/order_total.lbi');
        }
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_integral')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉绉?垎
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $points    = floatval($_GET['points']);
    $user_info = user_info($_SESSION['user_id']);

    /* 鍙栧緱璁㈠崟淇℃伅 */
    $order = flow_order_info();

    $flow_points = flow_available_points();  // 璇ヨ?鍗曞厑璁镐娇鐢ㄧ殑绉?垎
    $user_points = $user_info['pay_points']; // 鐢ㄦ埛鐨勭Н鍒嗘€绘暟

    if ($points > $user_points)
    {
        $result['error'] = $_LANG['integral_not_enough'];
    }
    elseif ($points > $flow_points)
    {
        $result['error'] = sprintf($_LANG['integral_too_much'], $flow_points);
    }
    else
    {
        /* 鍙栧緱璐?墿绫诲瀷 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        $order['integral'] = $points;

        /* 鑾峰緱鏀惰揣浜轰俊鎭 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
        $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

        if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
        {
            $result['error'] = $_LANG['no_goods_in_cart'];
        }
        else
        {
            /* 璁＄畻璁㈠崟鐨勮垂鐢 */
            $total = order_fee($order, $cart_goods, $consignee);
            $smarty->assign('total',  $total);
            $smarty->assign('config', $_CFG);

            /* 鍥㈣喘鏍囧織 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

            $result['content'] = $smarty->fetch('library/order_total.lbi');
            $result['error'] = '';
        }
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_bonus')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉绾㈠寘
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        $bonus = bonus_info(intval($_GET['bonus']));

        if ((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || $_GET['bonus'] == 0)
        {
            $order['bonus_id'] = intval($_GET['bonus']);
        }
        else
        {
            $order['bonus_id'] = 0;
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_needinv')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉鍙戠エ鐨勮?缃
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');
    $json = new JSON();
    $_GET['inv_type'] = !empty($_GET['inv_type']) ? json_str_iconv(urldecode($_GET['inv_type'])) : '';
    $_GET['invPayee'] = !empty($_GET['invPayee']) ? json_str_iconv(urldecode($_GET['invPayee'])) : '';
    $_GET['inv_content'] = !empty($_GET['inv_content']) ? json_str_iconv(urldecode($_GET['inv_content'])) : '';

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        die($json->encode($result));
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();

        if (isset($_GET['need_inv']) && intval($_GET['need_inv']) == 1)
        {
            $order['need_inv']    = 1;
            $order['inv_type']    = trim(stripslashes($_GET['inv_type']));
            $order['inv_payee']   = trim(stripslashes($_GET['inv_payee']));
            $order['inv_content'] = trim(stripslashes($_GET['inv_content']));
        }
        else
        {
            $order['need_inv']    = 0;
            $order['inv_type']    = '';
            $order['inv_payee']   = '';
            $order['inv_content'] = '';
        }

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        die($smarty->fetch('library/order_total.lbi'));
    }
}
elseif ($_REQUEST['step'] == 'change_oos')
{
    /*------------------------------------------------------ */
    //-- 鏀瑰彉缂鸿揣澶勭悊鏃剁殑鏂瑰紡
    /*------------------------------------------------------ */

    /* 鍙栧緱璁㈠崟淇℃伅 */
    $order = flow_order_info();

    $order['how_oos'] = intval($_GET['oos']);

    /* 淇濆瓨 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'check_surplus')
{
    /*------------------------------------------------------ */
    //-- 妫€鏌ョ敤鎴疯緭鍏ョ殑浣欓?
    /*------------------------------------------------------ */
    $surplus   = floatval($_GET['surplus']);
    $user_info = user_info($_SESSION['user_id']);

    if (($user_info['user_money'] + $user_info['credit_line'] < $surplus))
    {
        die($_LANG['surplus_not_enough']);
    }

    exit;
}
elseif ($_REQUEST['step'] == 'check_integral')
{
    /*------------------------------------------------------ */
    //-- 妫€鏌ョ敤鎴疯緭鍏ョ殑浣欓?
    /*------------------------------------------------------ */
    $points      = floatval($_GET['integral']);
    $user_info   = user_info($_SESSION['user_id']);
    $flow_points = flow_available_points();  // 璇ヨ?鍗曞厑璁镐娇鐢ㄧ殑绉?垎
    $user_points = $user_info['pay_points']; // 鐢ㄦ埛鐨勭Н鍒嗘€绘暟

    if ($points > $user_points)
    {
        die($_LANG['integral_not_enough']);
    }

    if ($points > $flow_points)
    {
        die(sprintf($_LANG['integral_too_much'], $flow_points));
    }

    exit;
}
/*------------------------------------------------------ */
//-- 瀹屾垚鎵€鏈夎?鍗曟搷浣滐紝鎻愪氦鍒版暟鎹?簱
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'done')
{
    include_once('includes/lib_clips.php');
    include_once('includes/lib_payment.php');

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 妫€鏌ヨ喘鐗╄溅涓?槸鍚︽湁鍟嗗搧 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
        " WHERE session_id = '" . SESS_ID . "' " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }

    /* 妫€鏌ュ晢鍝佸簱瀛 */
    /* 濡傛灉浣跨敤搴撳瓨锛屼笖涓嬭?鍗曟椂鍑忓簱瀛橈紝鍒欏噺灏戝簱瀛 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
    {
        $cart_goods_stock = get_cart_goods();
        $_cart_goods_stock = array();
        foreach ($cart_goods_stock['goods_list'] as $value)
        {
            $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
        }
        flow_cart_stock($_cart_goods_stock);
        unset($cart_goods_stock, $_cart_goods_stock);
    }

    /*
     * 妫€鏌ョ敤鎴锋槸鍚﹀凡缁忕櫥褰
     * 濡傛灉鐢ㄦ埛宸茬粡鐧诲綍浜嗗垯妫€鏌ユ槸鍚︽湁榛樿?鐨勬敹璐у湴鍧€
     * 濡傛灉娌℃湁鐧诲綍鍒欒烦杞?埌鐧诲綍鍜屾敞鍐岄〉闈
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 鐢ㄦ埛娌℃湁鐧诲綍涓旀病鏈夐€夊畾鍖垮悕璐?墿锛岃浆鍚戝埌鐧诲綍椤甸潰 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

    $consignee = get_consignee($_SESSION['user_id']);

    /* 妫€鏌ユ敹璐т汉淇℃伅鏄?惁瀹屾暣 */
    if (!check_consignee_info($consignee, $flow_type))
    {
        /* 濡傛灉涓嶅畬鏁村垯杞?悜鍒版敹璐т汉淇℃伅濉?啓鐣岄潰 */
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }

    $_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
    $_POST['card_message'] = isset($_POST['card_message']) ? htmlspecialchars($_POST['card_message']) : '';
    $_POST['inv_type'] = !empty($_POST['inv_type']) ? htmlspecialchars($_POST['inv_type']) : '';
    $_POST['inv_payee'] = isset($_POST['inv_payee']) ? htmlspecialchars($_POST['inv_payee']) : '';
    $_POST['inv_content'] = isset($_POST['inv_content']) ? htmlspecialchars($_POST['inv_content']) : '';
    $_POST['postscript'] = isset($_POST['postscript']) ? htmlspecialchars($_POST['postscript']) : '';

    $order = array(
        'shipping_id'     => intval($_POST['shipping']),
        'pay_id'          => intval($_POST['payment']),
        'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
        'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,
        'card_message'    => trim($_POST['card_message']),
        'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
        'integral'        => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
        'bonus_id'        => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
        'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
        'inv_type'        => $_POST['inv_type'],
        'inv_payee'       => trim($_POST['inv_payee']),
        'inv_content'     => $_POST['inv_content'],
        'postscript'      => trim($_POST['postscript']),
        'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
        'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
        'user_id'         => $_SESSION['user_id'],
        'add_time'        => gmtime(),
        'order_status'    => OS_UNCONFIRMED,
        'shipping_status' => SS_UNSHIPPED,
        'pay_status'      => PS_UNPAYED,
        'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']))
        );

    /* 鎵╁睍淇℃伅 */
    if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS)
    {
        $order['extension_code'] = $_SESSION['extension_code'];
        $order['extension_id'] = $_SESSION['extension_id'];
    }
    else
    {
        $order['extension_code'] = '';
        $order['extension_id'] = 0;
    }

    /* 妫€鏌ョН鍒嗕綑棰濇槸鍚﹀悎娉 */
    $user_id = $_SESSION['user_id'];
    if ($user_id > 0)
    {
        $user_info = user_info($user_id);

        $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
        if ($order['surplus'] < 0)
        {
            $order['surplus'] = 0;
        }

        // 鏌ヨ?鐢ㄦ埛鏈夊?灏戠Н鍒
        $flow_points = flow_available_points();  // 璇ヨ?鍗曞厑璁镐娇鐢ㄧ殑绉?垎
        $user_points = $user_info['pay_points']; // 鐢ㄦ埛鐨勭Н鍒嗘€绘暟

        $order['integral'] = min($order['integral'], $user_points, $flow_points);
        if ($order['integral'] < 0)
        {
            $order['integral'] = 0;
        }
    }
    else
    {
        $order['surplus']  = 0;
        $order['integral'] = 0;
    }

    /* 妫€鏌ョ孩鍖呮槸鍚﹀瓨鍦 */
    if ($order['bonus_id'] > 0)
    {
        $bonus = bonus_info($order['bonus_id']);

        if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type))
        {
            $order['bonus_id'] = 0;
        }
    }
    elseif (isset($_POST['bonus_sn']))
    {
        $bonus_sn = trim($_POST['bonus_sn']);
        $bonus = bonus_info(0, $bonus_sn);
        $now = gmtime();
        if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type) || $now > $bonus['use_end_date'])
        {
        }
        else
        {
            if ($user_id > 0)
            {
                $sql = "UPDATE " . $ecs->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
                $db->query($sql);
            }
            $order['bonus_id'] = $bonus['bonus_id'];
            $order['bonus_sn'] = $bonus_sn;
        }
    }

    /* 璁㈠崟涓?殑鍟嗗搧 */
    $cart_goods = cart_goods($flow_type);

    if (empty($cart_goods))
    {
        show_message($_LANG['no_goods_in_cart'], $_LANG['back_home'], './', 'warning');
    }

    /* 妫€鏌ュ晢鍝佹€婚?鏄?惁杈惧埌鏈€浣庨檺璐?噾棰 */
    if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $_CFG['min_goods_amount'])
    {
        show_message(sprintf($_LANG['goods_amount_not_enough'], price_format($_CFG['min_goods_amount'], false)));
    }

    /* 鏀惰揣浜轰俊鎭 */
    foreach ($consignee as $key => $value)
    {
        $order[$key] = addslashes($value);
    }

   /* 鍒ゆ柇鏄?笉鏄?疄浣撳晢鍝 */
    foreach ($cart_goods AS $val)
    {
        /* 缁熻?瀹炰綋鍟嗗搧鐨勪釜鏁 */
        if ($val['is_real'])
        {
            $is_real_good=1;
        }
    }
    if(isset($is_real_good))
    {
        $sql="SELECT shipping_id FROM " . $ecs->table('shipping') . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1"; 
        if(!$db->getOne($sql))
        {
           show_message($_LANG['flow_no_shipping']);
        }
    }
    /* 璁㈠崟涓?殑鎬婚? */
    $total = order_fee($order, $cart_goods, $consignee);
    $order['bonus']        = $total['bonus'];
    $order['goods_amount'] = $total['goods_price'];
    $order['discount']     = $total['discount'];
    $order['surplus']      = $total['surplus'];
    $order['tax']          = $total['tax'];

    // 璐?墿杞︿腑鐨勫晢鍝佽兘浜?彈绾㈠寘鏀?粯鐨勬€婚?
    $discount_amout = compute_discount_amount();
    // 绾㈠寘鍜岀Н鍒嗘渶澶氳兘鏀?粯鐨勯噾棰濅负鍟嗗搧鎬婚?
    $temp_amout = $order['goods_amount'] - $discount_amout;
    if ($temp_amout <= 0)
    {
        $order['bonus_id'] = 0;
    }

    /* 閰嶉€佹柟寮 */
    if ($order['shipping_id'] > 0)
    {
        $shipping = shipping_info($order['shipping_id']);
        $order['shipping_name'] = addslashes($shipping['shipping_name']);
    }
    $order['shipping_fee'] = $total['shipping_fee'];
    $order['insure_fee']   = $total['shipping_insure'];

    /* 鏀?粯鏂瑰紡 */
    if ($order['pay_id'] > 0)
    {
        $payment = payment_info($order['pay_id']);
        $order['pay_name'] = addslashes($payment['pay_name']);
    }
    $order['pay_fee'] = $total['pay_fee'];
    $order['cod_fee'] = $total['cod_fee'];

    /* 鍟嗗搧鍖呰? */
    if ($order['pack_id'] > 0)
    {
        $pack               = pack_info($order['pack_id']);
        $order['pack_name'] = addslashes($pack['pack_name']);
    }
    $order['pack_fee'] = $total['pack_fee'];

    /* 绁濈?璐哄崱 */
    if ($order['card_id'] > 0)
    {
        $card               = card_info($order['card_id']);
        $order['card_name'] = addslashes($card['card_name']);
    }
    $order['card_fee']      = $total['card_fee'];

    $order['order_amount']  = number_format($total['amount'], 2, '.', '');

    /* 濡傛灉鍏ㄩ儴浣跨敤浣欓?鏀?粯锛屾?鏌ヤ綑棰濇槸鍚﹁冻澶 */
    if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
    {
        if($order['surplus'] >0) //浣欓?鏀?粯閲屽?鏋滆緭鍏ヤ簡涓€涓?噾棰
        {
            $order['order_amount'] = $order['order_amount'] + $order['surplus'];
            $order['surplus'] = 0;
        }
        if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
        {
            show_message($_LANG['balance_not_enough']);
        }
        else
        {
            $order['surplus'] = $order['order_amount'];
            $order['order_amount'] = 0;
        }
    }

    /* 濡傛灉璁㈠崟閲戦?涓?锛堜娇鐢ㄤ綑棰濇垨绉?垎鎴栫孩鍖呮敮浠橈級锛屼慨鏀硅?鍗曠姸鎬佷负宸茬‘璁ゃ€佸凡浠樻? */
    if ($order['order_amount'] <= 0)
    {
        $order['order_status'] = OS_CONFIRMED;
        $order['confirm_time'] = gmtime();
        $order['pay_status']   = PS_PAYED;
        $order['pay_time']     = gmtime();
        $order['order_amount'] = 0;
    }

    $order['integral_money']   = $total['integral_money'];
    $order['integral']         = $total['integral'];

    if ($order['extension_code'] == 'exchange_goods')
    {
        $order['integral_money']   = 0;
        $order['integral']         = $total['exchange_integral'];
    }

    $order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
    $order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';

    /* 璁板綍鎵╁睍淇℃伅 */
    if ($flow_type != CART_GENERAL_GOODS)
    {
        $order['extension_code'] = $_SESSION['extension_code'];
        $order['extension_id'] = $_SESSION['extension_id'];
    }

    $affiliate = unserialize($_CFG['affiliate']);
    if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
    {
        //鎺ㄨ崘璁㈠崟鍒嗘垚
        $parent_id = get_affiliate();
        if($user_id == $parent_id)
        {
            $parent_id = 0;
        }
    }
    elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
    {
        //鎺ㄨ崘娉ㄥ唽鍒嗘垚
        $parent_id = 0;
    }
    else
    {
        //鍒嗘垚鍔熻兘鍏抽棴
        $parent_id = 0;
    }
    $order['parent_id'] = $parent_id;

    /* 鎻掑叆璁㈠崟琛 */
    $error_no = 0;
    do
    {
        $order['order_sn'] = get_order_sn(); //鑾峰彇鏂拌?鍗曞彿
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');

        $error_no = $GLOBALS['db']->errno();

        if ($error_no > 0 && $error_no != 1062)
        {
            die($GLOBALS['db']->errorMsg());
        }
    }
    while ($error_no == 1062); //濡傛灉鏄??鍗曞彿閲嶅?鍒欓噸鏂版彁浜ゆ暟鎹

    $new_order_id = $db->insert_id();
    $order['order_id'] = $new_order_id;

    /* 鎻掑叆璁㈠崟鍟嗗搧 */
    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) ".
            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id".
            " FROM " .$ecs->table('cart') .
            " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
    $db->query($sql);
    /* 淇?敼鎷嶅崠娲诲姩鐘舵€ */
    if ($order['extension_code']=='auction')
    {
        $sql = "UPDATE ". $ecs->table('goods_activity') ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
        $db->query($sql);
    }

    /* 澶勭悊浣欓?銆佺Н鍒嗐€佺孩鍖 */
    if ($order['user_id'] > 0 && $order['surplus'] > 0)
    {
        log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($_LANG['pay_order'], $order['order_sn']));
    }
    if ($order['user_id'] > 0 && $order['integral'] > 0)
    {
        log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($_LANG['pay_order'], $order['order_sn']));
    }


    if ($order['bonus_id'] > 0 && $temp_amout > 0)
    {
        use_bonus($order['bonus_id'], $new_order_id);
    }

    /* 濡傛灉浣跨敤搴撳瓨锛屼笖涓嬭?鍗曟椂鍑忓簱瀛橈紝鍒欏噺灏戝簱瀛 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
    {
        change_order_goods_storage($order['order_id'], true, SDT_PLACE);
    }

    /* 缁欏晢瀹跺彂閭?欢 */
    /* 澧炲姞鏄?惁缁欏?鏈嶅彂閫侀偖浠堕€夐」 */
    if ($_CFG['send_service_email'] && $_CFG['service_email'] != '')
    {
        $tpl = get_mail_template('remind_of_new_order');
        $smarty->assign('order', $order);
        $smarty->assign('goods_list', $cart_goods);
        $smarty->assign('shop_name', $_CFG['shop_name']);
        $smarty->assign('send_date', date($_CFG['time_format']));
        $content = $smarty->fetch('str:' . $tpl['template_content']);
        send_mail($_CFG['shop_name'], $_CFG['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
    }

    /* 濡傛灉闇€瑕侊紝鍙戠煭淇 */
    if ($_CFG['sms_order_placed'] == '1' && $_CFG['sms_shop_mobile'] != '')
    {
        include_once('includes/cls_sms.php');
        $sms = new sms();
        $msg = $order['pay_status'] == PS_UNPAYED ?
            $_LANG['order_placed_sms'] : $_LANG['order_placed_sms'] . '[' . $_LANG['sms_paid'] . ']';
        $sms->send($_CFG['sms_shop_mobile'], sprintf($msg, $order['consignee'], $order['tel']),'', 13,1);
    }

    /* 濡傛灉璁㈠崟閲戦?涓? 澶勭悊铏氭嫙鍗 */
    if ($order['order_amount'] <= 0)
    {
        $sql = "SELECT goods_id, goods_name, goods_number AS num FROM ".
               $GLOBALS['ecs']->table('cart') .
                " WHERE is_real = 0 AND extension_code = 'virtual_card'".
                " AND session_id = '".SESS_ID."' AND rec_type = '$flow_type'";

        $res = $GLOBALS['db']->getAll($sql);

        $virtual_goods = array();
        foreach ($res AS $row)
        {
            $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
        }

        if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
        {
            /* 铏氭嫙鍗″彂璐 */
            if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
            {
                /* 濡傛灉娌℃湁瀹炰綋鍟嗗搧锛屼慨鏀瑰彂璐х姸鎬侊紝閫佺Н鍒嗗拰绾㈠寘 */
                $sql = "SELECT COUNT(*)" .
                        " FROM " . $ecs->table('order_goods') .
                        " WHERE order_id = '$order[order_id]' " .
                        " AND is_real = 1";
                if ($db->getOne($sql) <= 0)
                {
                    /* 淇?敼璁㈠崟鐘舵€ */
                    update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));

                    /* 濡傛灉璁㈠崟鐢ㄦ埛涓嶄负绌猴紝璁＄畻绉?垎锛屽苟鍙戠粰鐢ㄦ埛锛涘彂绾㈠寘 */
                    if ($order['user_id'] > 0)
                    {
                        /* 鍙栧緱鐢ㄦ埛淇℃伅 */
                        $user = user_info($order['user_id']);

                        /* 璁＄畻骞跺彂鏀剧Н鍒 */
                        $integral = integral_to_give($order);
                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));

                        /* 鍙戞斁绾㈠寘 */
                        send_order_bonus($order['order_id']);
                    }
                }
            }
        }

    }

    /* 娓呯┖璐?墿杞 */
    clear_cart($flow_type);
    /* 娓呴櫎缂撳瓨锛屽惁鍒欎拱浜嗗晢鍝侊紝浣嗘槸鍓嶅彴椤甸潰璇诲彇缂撳瓨锛屽晢鍝佹暟閲忎笉鍑忓皯 */
    clear_all_files();

    /* 鎻掑叆鏀?粯鏃ュ織 */
    $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

    /* 鍙栧緱鏀?粯淇℃伅锛岀敓鎴愭敮浠樹唬鐮 */
    if ($order['order_amount'] > 0)
    {
        $payment = payment_info($order['pay_id']);

        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

        $pay_obj    = new $payment['pay_code'];

        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));

        $order['pay_desc'] = $payment['pay_desc'];

        $smarty->assign('pay_online', $pay_online);
    }
    if(!empty($order['shipping_name']))
    {
        $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
    }

    /* 璁㈠崟淇℃伅 */
    $smarty->assign('order',      $order);
    $smarty->assign('total',      $total);
    $smarty->assign('goods_list', $cart_goods);
    $smarty->assign('order_submit_back', sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center'])); // 杩斿洖鎻愮ず

    user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //鎺ㄩ€乫eed鍒皍c
    unset($_SESSION['flow_consignee']); // 娓呴櫎session涓?繚瀛樼殑鏀惰揣浜轰俊鎭
    unset($_SESSION['flow_order']);
    unset($_SESSION['direct_shopping']);
}

/*------------------------------------------------------ */
//-- 鏇存柊璐?墿杞
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'update_cart')
{
    if (isset($_POST['goods_number']) && is_array($_POST['goods_number']))
    {
        flow_update_cart($_POST['goods_number']);
    }

    show_message($_LANG['update_cart_notice'], $_LANG['back_to_cart'], 'flow.php');
    exit;
}

/*------------------------------------------------------ */
//-- 鍒犻櫎璐?墿杞︿腑鐨勫晢鍝
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'drop_goods')
{
    $rec_id = intval($_GET['id']);
    flow_drop_cart_goods($rec_id);

    ecs_header("Location: flow.php\n");
    exit;
}

/* 鎶婁紭鎯犳椿鍔ㄥ姞鍏ヨ喘鐗╄溅 */
elseif ($_REQUEST['step'] == 'add_favourable')
{
    /* 鍙栧緱浼樻儬娲诲姩淇℃伅 */
    $act_id = intval($_POST['act_id']);
    $favourable = favourable_info($act_id);
    if (empty($favourable))
    {
        show_message($_LANG['favourable_not_exist']);
    }

    /* 鍒ゆ柇鐢ㄦ埛鑳藉惁浜?彈璇ヤ紭鎯 */
    if (!favourable_available($favourable))
    {
        show_message($_LANG['favourable_not_available']);
    }

    /* 妫€鏌ヨ喘鐗╄溅涓?槸鍚﹀凡鏈夎?浼樻儬 */
    $cart_favourable = cart_favourable();
    if (favourable_used($favourable, $cart_favourable))
    {
        show_message($_LANG['favourable_used']);
    }

    /* 璧犲搧锛堢壒鎯犲搧锛変紭鎯 */
    if ($favourable['act_type'] == FAT_GOODS)
    {
        /* 妫€鏌ユ槸鍚﹂€夋嫨浜嗚禒鍝 */
        if (empty($_POST['gift']))
        {
            show_message($_LANG['pls_select_gift']);
        }

        /* 妫€鏌ユ槸鍚﹀凡鍦ㄨ喘鐗╄溅 */
        $sql = "SELECT goods_name" .
                " FROM " . $ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                " AND is_gift = '$act_id'" .
                " AND goods_id " . db_create_in($_POST['gift']);
        $gift_name = $db->getCol($sql);
        if (!empty($gift_name))
        {
            show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
        }

        /* 妫€鏌ユ暟閲忔槸鍚﹁秴杩囦笂闄 */
        $count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
        if ($favourable['act_type_ext'] > 0 && $count + count($_POST['gift']) > $favourable['act_type_ext'])
        {
            show_message($_LANG['gift_count_exceed']);
        }

        /* 娣诲姞璧犲搧鍒拌喘鐗╄溅 */
        foreach ($favourable['gift'] as $gift)
        {
            if (in_array($gift['id'], $_POST['gift']))
            {
                add_gift_to_cart($act_id, $gift['id'], $gift['price']);
            }
        }
    }
    elseif ($favourable['act_type'] == FAT_DISCOUNT)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
    }
    elseif ($favourable['act_type'] == FAT_PRICE)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext']);
    }

    /* 鍒锋柊璐?墿杞 */
    ecs_header("Location: flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'clear')
{
    $sql = "DELETE FROM " . $ecs->table('cart') . " WHERE session_id='" . SESS_ID . "'";
    $db->query($sql);

    ecs_header("Location:./\n");
}
elseif ($_REQUEST['step'] == 'drop_to_collect')
{
    if ($_SESSION['user_id'] > 0)
    {
        $rec_id = intval($_GET['id']);
        $goods_id = $db->getOne("SELECT  goods_id FROM " .$ecs->table('cart'). " WHERE rec_id = '$rec_id' AND session_id = '" . SESS_ID . "' ");
        $count = $db->getOne("SELECT goods_id FROM " . $ecs->table('collect_goods') . " WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'");
        if (empty($count))
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
            $db->query($sql);
        }
        flow_drop_cart_goods($rec_id);
    }
    ecs_header("Location: flow.php\n");
    exit;
}

/* 楠岃瘉绾㈠寘搴忓垪鍙 */
elseif ($_REQUEST['step'] == 'validate_bonus')
{
    $bonus_sn = trim($_REQUEST['bonus_sn']);
    if (is_numeric($bonus_sn))
    {
        $bonus = bonus_info(0, $bonus_sn);
    }
    else
    {
        $bonus = array();
    }

//    if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0)
//    {
//        die($_LANG['bonus_sn_error']);
//    }
//    if ($bonus['min_goods_amount'] > cart_amount())
//    {
//        die(sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false)));
//    }
//    die(sprintf($_LANG['bonus_is_ok'], price_format($bonus['type_money'], false)));
    $bonus_kill = price_format($bonus['type_money'], false);

    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

    /* 鍙栧緱璐?墿绫诲瀷 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 鑾峰緱鏀惰揣浜轰俊鎭 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 瀵瑰晢鍝佷俊鎭?祴鍊 */
    $cart_goods = cart_goods($flow_type); // 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 鍙栧緱璐?墿娴佺▼璁剧疆 */
        $smarty->assign('config', $_CFG);

        /* 鍙栧緱璁㈠崟淇℃伅 */
        $order = flow_order_info();


        if (((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || ($bonus['type_money'] > 0 && empty($bonus['user_id']))) && $bonus['order_id'] <= 0)
        {
            //$order['bonus_kill'] = $bonus['type_money'];
            $now = gmtime();
            if ($now > $bonus['use_end_date'])
            {
                $order['bonus_id'] = '';
                $result['error']=$_LANG['bonus_use_expire'];
            }
            else
            {
                $order['bonus_id'] = $bonus['bonus_id'];
                $order['bonus_sn'] = $bonus_sn;
            }
        }
        else
        {
            //$order['bonus_kill'] = 0;
            $order['bonus_id'] = '';
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 璁＄畻璁㈠崟鐨勮垂鐢 */
        $total = order_fee($order, $cart_goods, $consignee);

        if($total['goods_price']<$bonus['min_goods_amount'])
        {
         $order['bonus_id'] = '';
         /* 閲嶆柊璁＄畻璁㈠崟 */
         $total = order_fee($order, $cart_goods, $consignee);
         $result['error'] = sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false));
        }

        $smarty->assign('total', $total);

        /* 鍥㈣喘鏍囧織 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }
    $json = new JSON();
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 娣诲姞绀煎寘鍒拌喘鐗╄溅
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'add_package_to_cart')
{
    include_once('includes/cls_json.php');
    $_POST['package_info'] = json_str_iconv($_POST['package_info']);

    $result = array('error' => 0, 'message' => '', 'content' => '', 'package_id' => '');
    $json  = new JSON;

    if (empty($_POST['package_info']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $package = $json->decode($_POST['package_info']);

    /* 濡傛灉鏄?竴姝ヨ喘鐗╋紝鍏堟竻绌鸿喘鐗╄溅 */
    if ($_CFG['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 鍟嗗搧鏁伴噺鏄?惁鍚堟硶 */
    if (!is_numeric($package->number) || intval($package->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    else
    {
        /* 娣诲姞鍒拌喘鐗╄溅 */
        if (add_package_to_cart($package->package_id, $package->number))
        {
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }

            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_CFG['one_step_buy'];
        }
        else
        {
            $result['message']    = $err->last_message();
            $result['error']      = $err->error_no;
            $result['package_id'] = stripslashes($package->package_id);
        }
    }
    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
else
{
    /* 鏍囪?璐?墿娴佺▼涓烘櫘閫氬晢鍝 */
    $_SESSION['flow_type'] = CART_GENERAL_GOODS;

    /* 濡傛灉鏄?竴姝ヨ喘鐗╋紝璺冲埌缁撶畻涓?績 */
    if ($_CFG['one_step_buy'] == '1')
    {
        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }

    /* 鍙栧緱鍟嗗搧鍒楄〃锛岃?绠楀悎璁 */
    $cart_goods = get_cart_goods();
    $smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);

    //璐?墿杞︾殑鎻忚堪鐨勬牸寮忓寲
    $smarty->assign('shopping_money',         sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']));
    $smarty->assign('market_price_desc',      sprintf($_LANG['than_market_price'],
        $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']));

    // 鏄剧ず鏀惰棌澶瑰唴鐨勫晢鍝
    if ($_SESSION['user_id'] > 0)
    {
        require_once(ROOT_PATH . 'includes/lib_clips.php');
        $collection_goods = get_collection_goods($_SESSION['user_id']);
        $smarty->assign('collection_goods', $collection_goods);
    }

    /* 鍙栧緱浼樻儬娲诲姩 */
    $favourable_list = favourable_list($_SESSION['user_rank']);
    usort($favourable_list, 'cmp_favourable');

    $smarty->assign('favourable_list', $favourable_list);

    /* 璁＄畻鎶樻墸 */
    $discount = compute_discount();
    $smarty->assign('discount', $discount['discount']);
    $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
    $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));

    /* 澧炲姞鏄?惁鍦ㄨ喘鐗╄溅閲屾樉绀哄晢鍝佸浘 */
    $smarty->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);

    /* 澧炲姞鏄?惁鍦ㄨ喘鐗╄溅閲屾樉绀哄晢鍝佸睘鎬 */
    $smarty->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);

    /* 璐?墿杞︿腑鍟嗗搧閰嶄欢鍒楄〃 */
    //鍙栧緱璐?墿杞︿腑鍩烘湰浠禝D
    $sql = "SELECT goods_id " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND is_gift = 0 " .
            "AND extension_code <> 'package_buy' " .
            "AND parent_id = 0 ";
    $parent_list = $GLOBALS['db']->getCol($sql);

    $fittings_list = get_goods_fittings($parent_list);

    $smarty->assign('fittings_list', $fittings_list);
}

$smarty->assign('currency_format', $_CFG['currency_format']);
$smarty->assign('integral_scale',  $_CFG['integral_scale']);
$smarty->assign('step',            $_REQUEST['step']);
assign_dynamic('shopping_flow');

$smarty->display('flow.dwt');

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 鑾峰緱鐢ㄦ埛鐨勫彲鐢ㄧН鍒
 *
 * @access  private
 * @return  integral
 */
function flow_available_points()
{
    $sql = "SELECT SUM(g.integral * c.goods_number) ".
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";

    $val = intval($GLOBALS['db']->getOne($sql));

    return integral_of_value($val);
}

/**
 * 鏇存柊璐?墿杞︿腑鐨勫晢鍝佹暟閲
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart($arr)
{
    /* 澶勭悊 */
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0 && !is_numeric($key))
        {
            continue;
        }

        //鏌ヨ?锛
        $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //鏌ヨ?锛氱郴缁熷惎鐢ㄤ簡搴撳瓨锛屾?鏌ヨ緭鍏ョ殑鍟嗗搧鏁伴噺鏄?惁鏈夋晥
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
                exit;
            }
            /* 鏄?揣鍝 */
            $goods['product_id'] = trim($goods['product_id']);
            if (!empty($goods['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";

                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $product_number['product_number'], $product_number['product_number']));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }

        /* 鏌ヨ?锛氭?鏌ヨ?椤规槸鍚︿负鍩烘湰浠 浠ュ強鏄?惁瀛樺湪閰嶄欢 */
        /* 姝ゅ?閰嶄欢鏄?寚娣诲姞鍟嗗搧鏃堕檮鍔犵殑骞朵笖鏄??缃?簡浼樻儬浠锋牸鐨勯厤浠 姝ょ被閰嶄欢閮芥湁parent_id goods_number涓? */
        $sql = "SELECT b.goods_number, b.rec_id
                FROM " .$GLOBALS['ecs']->table('cart') . " a, " .$GLOBALS['ecs']->table('cart') . " b
                WHERE a.rec_id = '$key'
                AND a.session_id = '" . SESS_ID . "'
                AND a.extension_code <> 'package_buy'
                AND b.parent_id = a.goods_id
                AND b.session_id = '" . SESS_ID . "'";

        $offers_accessories_res = $GLOBALS['db']->query($sql);

        //璁㈣揣鏁伴噺澶т簬0
        if ($val > 0)
        {
            /* 鍒ゆ柇鏄?惁涓鸿秴鍑烘暟閲忕殑浼樻儬浠锋牸鐨勯厤浠 鍒犻櫎*/
            $row_num = 1;
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                if ($row_num > $val)
                {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                            " WHERE session_id = '" . SESS_ID . "' " .
                            "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                    $GLOBALS['db']->query($sql);
                }

                $row_num ++;
            }

            /* 澶勭悊瓒呭€肩ぜ鍖 */
            if ($goods['extension_code'] == 'package_buy')
            {
                //鏇存柊璐?墿杞︿腑鐨勫晢鍝佹暟閲
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
            }
            /* 澶勭悊鏅?€氬晢鍝佹垨闈炰紭鎯犵殑閰嶄欢 */
            else
            {
                $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                $goods_price = get_final_price($goods['goods_id'], $val, true, $attr_id);

                //鏇存柊璐?墿杞︿腑鐨勫晢鍝佹暟閲
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
            }
        }
        //璁㈣揣鏁伴噺绛変簬0
        else
        {
            /* 濡傛灉鏄?熀鏈?欢骞朵笖鏈変紭鎯犱环鏍肩殑閰嶄欢鍒欏垹闄や紭鎯犱环鏍肩殑閰嶄欢 */
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                        " WHERE session_id = '" . SESS_ID . "' " .
                        "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }

            $sql = "DELETE FROM " .$GLOBALS['ecs']->table('cart').
                " WHERE rec_id='$key' AND session_id='" .SESS_ID. "'";
        }

        $GLOBALS['db']->query($sql);
    }

    /* 鍒犻櫎鎵€鏈夎禒鍝 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" .SESS_ID. "' AND is_gift <> 0";
    $GLOBALS['db']->query($sql);
}

/**
 * 妫€鏌ヨ?鍗曚腑鍟嗗搧搴撳瓨
 *
 * @access  public
 * @param   array   $arr
 *
 * @return  void
 */
function flow_cart_stock($arr)
{
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0)
        {
            continue;
        }

        $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //绯荤粺鍚?敤浜嗗簱瀛橈紝妫€鏌ヨ緭鍏ョ殑鍟嗗搧鏁伴噺鏄?惁鏈夋晥
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
                exit;
            }

            /* 鏄?揣鍝 */
            $row['product_id'] = trim($row['product_id']);
            if (!empty($row['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $row['goods_number'], $row['goods_number']));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }
    }

}

/**
 * 鍒犻櫎璐?墿杞︿腑鐨勫晢鍝
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
function flow_drop_cart_goods($id)
{
    /* 鍙栧緱鍟嗗搧id */
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
    $row = $GLOBALS['db']->getRow($sql);
    if ($row)
    {
        //濡傛灉鏄?秴鍊肩ぜ鍖
        if ($row['extension_code'] == 'package_buy')
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND rec_id = '$id' LIMIT 1";
        }

        //濡傛灉鏄?櫘閫氬晢鍝侊紝鍚屾椂鍒犻櫎鎵€鏈夎禒鍝佸強鍏堕厤浠
        elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0)
        {
            /* 妫€鏌ヨ喘鐗╄溅涓??鏅?€氬晢鍝佺殑涓嶅彲鍗曠嫭閿€鍞?殑閰嶄欢骞跺垹闄 */
            $sql = "SELECT c.rec_id
                    FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods'). " AS g
                    WHERE gg.parent_id = '" . $row['goods_id'] . "'
                    AND c.goods_id = gg.goods_id
                    AND c.parent_id = '" . $row['goods_id'] . "'
                    AND c.extension_code <> 'package_buy'
                    AND gg.goods_id = g.goods_id
                    AND g.is_alone_sale = 0";
            $res = $GLOBALS['db']->query($sql);
            $_del_str = $id . ',';
            while ($id_alone_sale_goods = $GLOBALS['db']->fetchRow($res))
            {
                $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
            }
            $_del_str = trim($_del_str, ',');

            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
        }

        //濡傛灉涓嶆槸鏅?€氬晢鍝侊紝鍙?垹闄よ?鍟嗗搧鍗冲彲
        else
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE session_id = '" . SESS_ID . "' " .
                    "AND rec_id = '$id' LIMIT 1";
        }

        $GLOBALS['db']->query($sql);
    }

    flow_clear_cart_alone();
}

/**
 * 鍒犻櫎璐?墿杞︿腑涓嶈兘鍗曠嫭閿€鍞?殑鍟嗗搧
 *
 * @access  public
 * @return  void
 */
function flow_clear_cart_alone()
{
    /* 鏌ヨ?锛氳喘鐗╄溅涓?墍鏈変笉鍙?互鍗曠嫭閿€鍞?殑閰嶄欢 */
    $sql = "SELECT c.rec_id, gg.parent_id
            FROM " . $GLOBALS['ecs']->table('cart') . " AS c
                LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
                LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
            WHERE c.session_id = '" . SESS_ID . "'
            AND c.extension_code <> 'package_buy'
            AND gg.parent_id > 0
            AND g.is_alone_sale = 0";
    $res = $GLOBALS['db']->query($sql);
    $rec_id = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $rec_id[$row['rec_id']][] = $row['parent_id'];
    }

    if (empty($rec_id))
    {
        return;
    }

    /* 鏌ヨ?锛氳喘鐗╄溅涓?墍鏈夊晢鍝 */
    $sql = "SELECT DISTINCT goods_id
            FROM " . $GLOBALS['ecs']->table('cart') . "
            WHERE session_id = '" . SESS_ID . "'
            AND extension_code <> 'package_buy'";
    $res = $GLOBALS['db']->query($sql);
    $cart_good = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $cart_good[] = $row['goods_id'];
    }

    if (empty($cart_good))
    {
        return;
    }

    /* 濡傛灉璐?墿杞︿腑涓嶅彲浠ュ崟鐙?攢鍞?厤浠剁殑鍩烘湰浠朵笉瀛樺湪鍒欏垹闄よ?閰嶄欢 */
    $del_rec_id = '';
    foreach ($rec_id as $key => $value)
    {
        foreach ($value as $v)
        {
            if (in_array($v, $cart_good))
            {
                continue 2;
            }
        }

        $del_rec_id = $key . ',';
    }
    $del_rec_id = trim($del_rec_id, ',');

    if ($del_rec_id == '')
    {
        return;
    }

    /* 鍒犻櫎 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ."
            WHERE session_id = '" . SESS_ID . "'
            AND rec_id IN ($del_rec_id)";
    $GLOBALS['db']->query($sql);
}

/**
 * 姣旇緝浼樻儬娲诲姩鐨勫嚱鏁帮紝鐢ㄤ簬鎺掑簭锛堟妸鍙?敤鐨勬帓鍦ㄥ墠闈?級
 * @param   array   $a      浼樻儬娲诲姩a
 * @param   array   $b      浼樻儬娲诲姩b
 * @return  int     鐩哥瓑杩斿洖0锛屽皬浜庤繑鍥?1锛屽ぇ浜庤繑鍥?
 */
function cmp_favourable($a, $b)
{
    if ($a['available'] == $b['available'])
    {
        if ($a['sort_order'] == $b['sort_order'])
        {
            return 0;
        }
        else
        {
            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
        }
    }
    else
    {
        return $a['available'] ? -1 : 1;
    }
}

/**
 * 鍙栧緱鏌愮敤鎴风瓑绾у綋鍓嶆椂闂村彲浠ヤ韩鍙楃殑浼樻儬娲诲姩
 * @param   int     $user_rank      鐢ㄦ埛绛夌骇id锛?琛ㄧず闈炰細鍛
 * @return  array
 */
function favourable_list($user_rank)
{
    /* 璐?墿杞︿腑宸叉湁鐨勪紭鎯犳椿鍔ㄥ強鏁伴噺 */
    $used_list = cart_favourable();

    /* 褰撳墠鐢ㄦ埛鍙?韩鍙楃殑浼樻儬娲诲姩 */
    $favourable_list = array();
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();
    $sql = "SELECT * " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND start_time <= '$now' AND end_time >= '$now'" .
            " AND act_type = '" . FAT_GOODS . "'" .
            " ORDER BY sort_order";
    $res = $GLOBALS['db']->query($sql);
    while ($favourable = $GLOBALS['db']->fetchRow($res))
    {
        $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
        $favourable['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
        $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
        $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
        $favourable['gift']       = unserialize($favourable['gift']);

        foreach ($favourable['gift'] as $key => $value)
        {
            $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
            $is_sale = $GLOBALS['db']->getOne($sql);
            if(!$is_sale)
            {
                unset($favourable['gift'][$key]);
            }
        }

        $favourable['act_range_desc'] = act_range_desc($favourable);
        $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);

        /* 鏄?惁鑳戒韩鍙 */
        $favourable['available'] = favourable_available($favourable);
        if ($favourable['available'])
        {
            /* 鏄?惁灏氭湭浜?彈 */
            $favourable['available'] = !favourable_used($favourable, $used_list);
        }

        $favourable_list[] = $favourable;
    }

    return $favourable_list;
}

/**
 * 鏍规嵁璐?墿杞﹀垽鏂?槸鍚﹀彲浠ヤ韩鍙楁煇浼樻儬娲诲姩
 * @param   array   $favourable     浼樻儬娲诲姩淇℃伅
 * @return  bool
 */
function favourable_available($favourable)
{
    /* 浼氬憳绛夌骇鏄?惁绗﹀悎 */
    $user_rank = $_SESSION['user_rank'];
    if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false)
    {
        return false;
    }

    /* 浼樻儬鑼冨洿鍐呯殑鍟嗗搧鎬婚? */
    $amount = cart_favourable_amount($favourable);

    /* 閲戦?涓婇檺涓?琛ㄧず娌℃湁涓婇檺 */
    return $amount >= $favourable['min_amount'] &&
        ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
}

/**
 * 鍙栧緱浼樻儬鑼冨洿鎻忚堪
 * @param   array   $favourable     浼樻儬娲诲姩
 * @return  string
 */
function act_range_desc($favourable)
{
    if ($favourable['act_range'] == FAR_BRAND)
    {
        $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
                " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        $sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
                " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    elseif ($favourable['act_range'] == FAR_GOODS)
    {
        $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    else
    {
        return '';
    }
}

/**
 * 鍙栧緱璐?墿杞︿腑宸叉湁鐨勪紭鎯犳椿鍔ㄥ強鏁伴噺
 * @return  array
 */
function cart_favourable()
{
    $list = array();
    $sql = "SELECT is_gift, COUNT(*) AS num " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "'" .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " AND is_gift > 0" .
            " GROUP BY is_gift";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $list[$row['is_gift']] = $row['num'];
    }

    return $list;
}

/**
 * 璐?墿杞︿腑鏄?惁宸茬粡鏈夋煇浼樻儬
 * @param   array   $favourable     浼樻儬娲诲姩
 * @param   array   $cart_favourable璐?墿杞︿腑宸叉湁鐨勪紭鎯犳椿鍔ㄥ強鏁伴噺
 */
function favourable_used($favourable, $cart_favourable)
{
    if ($favourable['act_type'] == FAT_GOODS)
    {
        return isset($cart_favourable[$favourable['act_id']]) &&
            $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
            $favourable['act_type_ext'] > 0;
    }
    else
    {
        return isset($cart_favourable[$favourable['act_id']]);
    }
}

/**
 * 娣诲姞浼樻儬娲诲姩锛堣禒鍝侊級鍒拌喘鐗╄溅
 * @param   int     $act_id     浼樻儬娲诲姩id
 * @param   int     $id         璧犲搧id
 * @param   float   $price      璧犲搧浠锋牸
 */
function add_gift_to_cart($act_id, $id, $price)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
            "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, ".
                "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
            "FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id = '$id'";
    $GLOBALS['db']->query($sql);
}

/**
 * 娣诲姞浼樻儬娲诲姩锛堥潪璧犲搧锛夊埌璐?墿杞
 * @param   int     $act_id     浼樻儬娲诲姩id
 * @param   string  $act_name   浼樻儬娲诲姩name
 * @param   float   $amount     浼樻儬閲戦?
 */
function add_favourable_to_cart($act_id, $act_name, $amount)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
            "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, ".
                "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 鍙栧緱璐?墿杞︿腑鏌愪紭鎯犳椿鍔ㄨ寖鍥村唴鐨勬€婚噾棰
 * @param   array   $favourable     浼樻儬娲诲姩
 * @return  float
 */
function cart_favourable_amount($favourable)
{
    /* 鏌ヨ?浼樻儬鑼冨洿鍐呭晢鍝佹€婚?鐨剆ql */
    $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND c.session_id = '" . SESS_ID . "' " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND c.is_gift = 0 " .
            "AND c.goods_id > 0 ";

    /* 鏍规嵁浼樻儬鑼冨洿淇??sql */
    if ($favourable['act_range'] == FAR_ALL)
    {
        // sql do not change
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        /* 鍙栧緱浼樻儬鑼冨洿鍒嗙被鐨勬墍鏈変笅绾у垎绫 */
        $id_list = array();
        $cat_list = explode(',', $favourable['act_range_ext']);
        foreach ($cat_list as $id)
        {
            $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
        }

        $sql .= "AND g.cat_id " . db_create_in($id_list);
    }
    elseif ($favourable['act_range'] == FAR_BRAND)
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.brand_id " . db_create_in($id_list);
    }
    else
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.goods_id " . db_create_in($id_list);
    }

    /* 浼樻儬鑼冨洿鍐呯殑鍟嗗搧鎬婚? */
    return $GLOBALS['db']->getOne($sql);
}
?>