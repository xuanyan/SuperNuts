<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class DBQuery
{
    private $sql = '';
    private $param = array();

    public function select($field = '*')
    {
        $this->sql = "SELECT $field";

        return $this;
    }

    public function set($key, $value = null, $param = null)
    {
        $set = array();
        if (is_array($key)) {
            $set = $key;
            if ($value) {
                $param = $value;
            }
        } else {
            $set[$key] = $value;
            if ($param) {
                $param = array($param);
            }
        }

        if ($param) {
            $this->param = array_merge($this->param, $param);
        }

        $this->sql .= " SET " . implode(', ', $set);

        return $this;
    }

    public function update($table)
    {
        $this->sql = "UPDATE $table";

        return $this;
    }

    public function delete()
    {
        $this->sql = "DELETE";

        return $this;
    }

    public function getParam()
    {
        return $this->param;
    }

    public function __toString()
    {
        return $this->sql;
    }

    public function from($table)
    {
        $this->sql .= " FROM $table";

        return $this;
    }

    public function _where()
    {
        $param = func_get_args();
        $sql = array_shift($param);
        $this->sql .= " $sql";
        if ($param) {
            if (is_array($param[0])) {
                $param = $param[0];
            }
            $this->param = array_merge($this->param, $param);
        }

        return $this;
    }

    public function limit($string)
    {
        $this->sql .= " LIMIT $string";

        return $this;
    }

    public function orderBy($sql)
    {
        $this->sql .= " ORDER BY $sql";

        return $this;
    }

    public function groupBy($sql)
    {
        $this->sql .= " GROUP BY $sql";

        return $this;
    }

    public function leftJoin($sql)
    {
        $this->sql .= " LEFT JOIN $sql";

        return $this;
    }

    public function _wherein($key, $array)
    {
        $t = array_fill(0, count($array), '?');
        $this->sql .= " $key IN(".implode(',', $t).")";
        $this->param = array_merge($this->param, $array);
        return $this;
    }

    public function orWhereIn()
    {
        $this->sql .= ' OR';
        $param = func_get_args();

        return call_user_func_array(array($this, '_wherein'), $param);
    }

    public function andWhereIn()
    {
        $this->sql .= ' AND';
        $param = func_get_args();

        return call_user_func_array(array($this, '_wherein'), $param);
    }

    public function whereIn()
    {
        $this->sql .= ' WHERE';
        $param = func_get_args();

        return call_user_func_array(array($this, '_wherein'), $param);
    }

    public function andWhere()
    {
        $this->sql .= ' AND';
        $param = func_get_args();

        return call_user_func_array(array($this, '_where'), $param);
    }

    public function where()
    {
        $this->sql .= ' WHERE';
        $param = func_get_args();

        return call_user_func_array(array($this, '_where'), $param);
    }

    public function orWhere()
    {
        $this->sql .= ' OR';
        $param = func_get_args();

        return call_user_func_array(array($this, '_where'), $param);
    }
}

?>