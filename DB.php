<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class DB
{
    public static $sql = array();
    private static $connections = array();
    const NUM = 0;
    const ASSOC = 1;
    const BOTH = 2;

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

    public function getCol()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, DB::NUM)) {
            $rs[] = $rt[0];
        }

        return $rs;
    }

    public function getOne()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, DB::NUM);

        return $rs[0];
    }

    public function getAll()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this,'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, DB::ASSOC)) {
            $rs[] = $rt;
        }

        return $rs;
    }

    public function getRow()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, DB::ASSOC);

        return $rs === false ? array() : $rs;
    }

    public function getDriver()
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

?>