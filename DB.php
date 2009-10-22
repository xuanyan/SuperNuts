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

    public static function connect()
    {
        $params = func_get_args();

        if (count($params) == 1) {
            $params = $params[0];
        }

        $key = md5(serialize($params));

        if (!isset(self::$connections[$key])) {
            $driver = array_shift($params);

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

?>