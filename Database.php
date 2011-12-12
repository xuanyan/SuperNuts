<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class Database
{
    public static $sql = array();
    private static $connections = array();
    public static $instance = null;
    public static $debug = false;
    private $driver = null;

    const NUM = 0;
    const ASSOC = 1;
    const BOTH = 2;

    function __call($fun, $params = array())
    {
        return call_user_func_array(array($this->driver, $fun), $params);
    }

    function __construct()
    {
        $params = func_get_args();

        if (count($params) == 1) {
            $params = $params[0];
        }

        list($key, $sp) = self::getParamHash($params);

        $this->driver = self::getDriver($params, $sp);
    }

    private static function getDriver($params, $sp)
    {
        if (is_array($params)) {
            $driver = array_shift($params);
        } elseif (preg_match('/type \((\w+)|object\((\w+)\)/', $sp, $driver)) {
            $driver = strtolower(array_pop($driver));
            if ($driver == 'sqlitedatabase') {
                $driver = 'sqlite';
            }
        } else {
            throw new Exception("cant auto detect the database driver");
        }

        require_once dirname(__FILE__).'/Driver/Database/'.$driver.'.php';
        $class = $driver.'Wrapper';

        return new $class($params);
    }

    private static function getParamHash($params)
    {
        // mabe the param is object, so use var_dump
        ob_start();
        var_dump($params);
        $sp = ob_get_clean();
        $key = sha1($sp);
        // $key = md5(serialize($params));

        return array($key, $sp);
    }

    public static function connect()
    {
        $params = func_get_args();

        if (count($params) == 1) {
            $params = $params[0];
        }

        list($key, $sp) = self::getParamHash($params);

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = self::getDriver($params, $sp);
        }

        return self::$connections[$key];
    }
}

abstract class DatabaseAbstract
{
    protected $config = array();
    protected $link = null;
    public $initialization = array();

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
        while ($rt = $this->fetch($query, Database::NUM)) {
            $rs[] = $rt[0];
        }

        return $rs;
    }

    public function getOne()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, Database::NUM);

        return $rs[0];
    }

    public function getAll()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this,'query'), $param);

        $rs = array();
        while ($rt = $this->fetch($query, Database::ASSOC)) {
            $rs[] = $rt;
        }

        return $rs;
    }

    public function getRow()
    {
        $param = func_get_args();
        $query = call_user_func_array(array($this, 'query'), $param);
        $rs = $this->fetch($query, Database::ASSOC);

        return $rs === false ? array() : $rs;
    }

    public function getDriver()
    {
        return $this->initialization();
    }
}

interface DatabaseWrapper
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