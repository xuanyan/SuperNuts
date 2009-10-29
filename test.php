<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */


require './DB.php';

// $db = DB::connect('mysql', 'localhost', 'root', 'root', 'wejello');

// $db = DB::connect('mysqli', 'localhost', 'root', 'root', 'wejello');

// $db = DB::connect('pdo', 'mysql:dbname=wejello;host=localhost', 'root', 'root');
// $db = DB::connect('sqlite', './test.sqlite');


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