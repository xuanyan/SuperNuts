<?php

/**
 * alltosun.com 文件说明
 * ============================================================================
 * 版权所有 (C) 2007-2009 北京共创阳光科技有限公司，并保留所有权利。
 * 网站地址: http://www.alltosun.com
 * ----------------------------------------------------------------------------
 * 许可声明：这是一个开源程序，未经许可不得将本软件的整体或任何部分用于商业用途及再发布。
 * ============================================================================
 * $Author: 宣言 (xuany@alltosun.com) $
 * $Date: 2009-10-22 15:42:24 +0800 $
*/


require './DB.php';

// $db = DB::connect('mysql', 'localhost', 'root', 'root', 'wejello');

$db = DB::connect('mysqli', 'localhost', 'root', 'root', 'wejello');

// $db = DB::connect('pdo', 'mysql:dbname=wejello;host=localhost', 'root', 'root');

echo 1;
print_r($db->getRow("select * from member where id = ?", 4));
echo 2;
print_r($db->getAll("select * from member"));
echo 3;
print_r($db->getCol("select id from member"));
echo 4;
print_r($db->getOne("select count(*) from member"));

echo 5;
$query = $db->query("select * from member");
print_r($db->fetch($query));
?>