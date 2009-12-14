<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@wejello.org>
 * @version $Id: db.php 19 2009-06-24 14:59:53Z xuanyan $
 */

class DB
{
    public static $sql = array();
    private static $connections = array();
    const NUM = 0;
    const ASSOC = 1;
    const BOTH = 2;

    public static function query()
    {
        return new DBQuery();
    }

    public static function connect()
    {
        $params = func_get_args();

        if (count($params) == 1) {
            $params = $params[0];
        }

        // mabe the param is object, so use var_dump
        ob_start();
        var_dump($params);
        $sp = ob_get_clean();
        $key = sha1($sp);
        // $key = md5(serialize($params));

        if (!isset(self::$connections[$key])) {
            if (!is_array($params)) {
                if (!preg_match('/type \((\w+)|object\((\w+)\)/', $sp, $driver)) {
                    throw new Exception("cant detect the drive auto", 1);
                } else {
                    $driver = strtolower(array_pop($driver));
                    if ($driver == 'sqlitedatabase') {
                        $driver = 'sqlite';
                    }
                }
            } else {
                $driver = array_shift($params);
            }

            require_once dirname(__FILE__).'/Driver/'.$driver.'.php';
            $class = $driver.'Wrapper';
            self::$connections[$key] = new $class($params);
        }

        return self::$connections[$key];
    }
}

abstract class DBAbstract
{
    protected $config = array();
    protected $link = null;

    function __construct($config)
    {
        $this->config = $config;
        if (!is_array($this->config)) {
            $this->link = $this->config;
        }
    }

    function getDriver()
    {
        return $this->initialization();
    }
}

interface DBWrapper
{
    public function getRow();
    public function getCol();
    public function getOne();
    public function getAll();
    public function exec();
    public function lastInsertId();
    public function getDriver();
    public function query();
    public function fetch($query);
}

class DBQuery
{
    private $sql = '';
    private $param = array();

    public function select($field = '*')
    {
        $this->sql = "SELECT $field";

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