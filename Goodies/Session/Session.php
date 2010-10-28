<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class Session
{
    private static $config = array(
        'db'    => null,
        'table' => 'session',
        'user_type' => '',
        'insert_empty_data' => false
    );

    private static $now = null;
    private static $lifetime = null;

    public static function start($config)
    {
        self::$config = array_merge(self::$config, $config);

        self::$now = time();
        self::$lifetime  = ini_get('session.gc_maxlifetime');
        self::$client_ip = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] :
                     (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
                     (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'));

        session_set_save_handler(
            array(__CLASS__, 'open'),
            array(__CLASS__, 'close'),
            array(__CLASS__, 'read'),
            array(__CLASS__, 'write'),
            array(__CLASS__, 'destroy'),
            array(__CLASS__, 'gc')
        );

        session_start();
    }

    private static function open($path, $name)
    {
        return true;
    }

    public static function close()
    {
        return true;
    }

    private static function read($PHPSESSID)
    {
        $table = self::$config['table'];
        $db = self::$config['db'];

        $sql = "SELECT * FROM $table WHERE PHPSESSID = ?";

        if (!$result = $db->getRow($sql, $PHPSESSID)) {
            return '';
        }

        if (self::$client_ip != $result['client_ip']) {
            self::destroy($PHPSESSID);

            return '';
        }

        if (($result['update_time'] + self::$lifetime) < self::$now) {
            self::destroy($PHPSESSID);

            return '';
        }

        return $result['data'];
    }
    
    public static function write($PHPSESSID, $data)
    {
        $table = self::$config['table'];
        $db = self::$config['db'];
    
        $sql = "SELECT * FROM $table WHERE PHPSESSID = ?";

        // check the session if is exist
        if ($result = $db->getRow($sql, $PHPSESSID)) {
            // if there is no changes in 30s, not update
            if ($result['data'] != $data || self::$now > ($result['update_time'] + 30)) {
                $sql = "UPDATE $table SET update_time = ?, data = ?, user_type = ? WHERE PHPSESSID = ?";
                $db->exec($sql, self::$now, $data, $PHPSESSID, self::$config['user_type']);
            }
        } else {
            // if set config::insert_empty_data, then the empty data will not insert to the database
            if (!self::$config['insert_empty_data'] || !empty($data)) {
                $sql = "INSERT INTO $table (PHPSESSID, update_time, client_ip, user_type, data) VALUES (?, ?, ?, ?, ?)";
                $db->exec($sql, $PHPSESSID, self::$now, self::$client_ip, self::$config['user_type'], $data);
            }
        }

        return true;
    }

    public static function destroy($PHPSESSID)
    {
        $table = self::$config['table'];
        $db = self::$config['db'];

        $sql = "DELETE FROM $table WHERE PHPSESSID = ?";
        $db->exec($sql, $PHPSESSID);

        return true;
    }

    private static function gc($lifetime)
    {
        $table = self::$config['table'];
        $db = self::$config['db'];

        $sql = "DELETE FROM $table WHERE update_time < ?";

        $db->exec($sql, self::$now - $lifetime);

        return true;
    }

}
?>