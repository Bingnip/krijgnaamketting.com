<?php

define('IS_INC', TRUE);
require_once '../common/common.php';

$arrStatus = array(
    0=>array('name'=>'禁用','color'=>'red'),
    1=>array('name'=>'显示','color'=>'green'),
);
$tpl->assign('arrStatus', $arrStatus);

