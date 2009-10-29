<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class mysqlWrapper extends DBAbstract implements DBWrapper
{
    private function initialization()
    {
        if ($this->link === null) {
            $dbname = array_pop($this->config);
            $this->link = call_user_func_array('mysql_connect', $this->config);
            $version = mysql_get_server_info($this->link);
            mysql_unbuffered_query('SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary', $this->link);
            mysql_unbuffered_query("SET sql_mode=''", $this->link);
            mysql_select_db($dbname, $this->link);
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

        DB::$sql[] = $sql;

        $this->initialization();

        if (isset($params[0])) {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            foreach ($params as $key => $val) {
                $params[$key] = mysql_real_escape_string($val, $this->link);
            }
            $sql = str_replace('?', "'%s'", $sql);
            array_unshift($params, $sql);
            $sql = call_user_func_array('sprintf', $params);
        }

        $query = mysql_query($sql, $this->link);
        if ($query === false) {
            throw new Exception("Error sql query:$sql");
        }

        return $query;
    }

    public function exec()
    {
        $param = func_get_args();
        call_user_func_array(array($this,'query'), $param);

        return mysql_affected_rows($this->link);
    }

    public function fetch($query, $result_type = DB::ASSOC)
    {
        if ($result_type == DB::ASSOC) {
            return mysql_fetch_array($query, MYSQL_ASSOC);
        } elseif ($result_type == DB::NUM) {
            return mysql_fetch_array($query, MYSQL_NUM);
        }

        return mysql_fetch_array($query, MYSQL_BOTH);
    }

    public function getOne()
    {
        $param = func_get_args();
        if (stripos($param[0], 'limit') === false) {
            $param[0] .= ' LIMIT 1';
        }
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, DB::NUM);
        mysql_free_result($query);

        return $rs[0];
    }

    public function getRow()
    {
        $param = func_get_args();
        if (stripos($param[0], 'limit') === false) {
            $param[0] .= ' LIMIT 1';
        }
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, DB::ASSOC);
        mysql_free_result($query);

        return $rs === false ? array() : $rs;
    }

    public function getCol()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this,'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, DB::NUM)) {
            $rs[] = $rt[0];
        }
        mysql_free_result($query);

        return $rs;
    }

    public function getAll()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this,'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, DB::ASSOC)) {
            $rs[] = $rt;
        }
        mysql_free_result($query);

        return $rs;
    }

    public function lastInsertId()
    {
        $this->initialization();

        return mysql_insert_id($this->link);
    }
}



?>