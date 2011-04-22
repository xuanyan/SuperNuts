<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class mysqlWrapper extends DatabaseAbstract implements DatabaseWrapper
{
    // lazy loading
    private function initialization()
    {
        if ($this->link === null) {
            $dbname = array_pop($this->config);
            $this->link = call_user_func_array('mysql_connect', $this->config);
            mysql_select_db($dbname, $this->link);
            foreach ($this->initialization as $val) {
                mysql_unbuffered_query($val, $this->link);
            }
        }

        return $this->link;
    }

    public function query()
    {
        $params = func_get_args();
        $sql = array_shift($params);

        Database::$debug && Database::$sql[] = $sql;

        $this->initialization();

        if (isset($params[0])) {
            if (is_array($params[0])) {
                $params = $params[0];
            }
            foreach ($params as $key => $val) {
                $params[$key] = mysql_real_escape_string($val, $this->link);
            }
            if (preg_match_all('/:(\w+)/i', $sql, $tmp)) {
                $p = array();
                foreach ($tmp[1] as $key => $val) {
                    $p[] = $params[$val];
                }
                $params = $p;
                $sql = str_replace($tmp[0], '?', $sql);
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

    public function fetch($query, $result_type = Database::ASSOC)
    {
        if ($result_type == Database::ASSOC) {
            return mysql_fetch_array($query, MYSQL_ASSOC);
        } elseif ($result_type == Database::NUM) {
            return mysql_fetch_array($query, MYSQL_NUM);
        }

        return mysql_fetch_array($query, MYSQL_BOTH);
    }

    public function getOne()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, Database::NUM);
        mysql_free_result($query);

        return $rs[0];
    }

    public function getRow()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, Database::ASSOC);
        mysql_free_result($query);

        return $rs === false ? array() : $rs;
    }

    public function getCol()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this,'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, Database::NUM)) {
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
        while ($rt = $this->fetch($query, Database::ASSOC)) {
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