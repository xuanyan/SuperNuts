<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class Cache
{
    private static $connections = array();
    public static $instance = null;

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
                if (is_string($params)) {
                    $driver = 'file';
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

abstract class CacheAbstract
{
    protected $config = array();
    protected $ns = '';
    protected $now = 0;
}

interface CacheWrapper
{
    public function ns($key);
    public function delete($key = null);
    public function get($key);
    public function set($key, $value, $expire = 3600);
}

?>