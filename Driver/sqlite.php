<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class sqliteWrapper extends DBAbstract implements DBWrapper
{
    private function initialization()
    {
        if (!($this->link instanceof SQLiteDatabase)) {
            $this->link = call_user_func_array(
                array(new ReflectionClass('SQLiteDatabase'), 'newInstance'), $this->config);
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
                $params[$key] = sqlite_escape_string($val);
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

        $query = $this->link->query($sql);
        if ($query === false) {
            throw new Exception("Error sql query:$sql");
        }

        return $query;
    }

    public function exec()
    {
        $param = func_get_args();
        call_user_func_array(array($this, 'query'), $param);

        return $this->link->changes();
    }

    public function getCol()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        $rs = array();
        while ($rt = $query->fetch(SQLITE_NUM)) {
            $rs[] = $rt[0];
        }

        return $rs;
    }

    public function getOne()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        return $query->fetchSingle();
    }

    public function getRow()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        return $query->fetch(SQLITE_ASSOC);
    }

    public function fetch($query, $result_type = DB::ASSOC)
    {
        if ($result_type == DB::ASSOC) {
            return $query->fetch(SQLITE_ASSOC);
        } elseif ($result_type == DB::NUM) {
            return $query->fetch(SQLITE_NUM);
        }

        return $query->fetch(SQLITE_BOTH);
    }

    public function getAll()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        return $query->fetchAll(SQLITE_ASSOC);
    }

    public function lastInsertId()
    {
        return $this->initialization()->lastInsertRowid();
    }
}

?>