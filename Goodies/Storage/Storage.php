<?php

/*
 * This file is part of the wejello package.
 *
 * @copyright (c) 2009 WeJello Project More info http://www.wejello.org
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author XuanYan <xuanyan@wejello.org>
 * 
 */

// $config = array(
//     'db' => 
//     'index_table' => 
//     'mate_table' => 
//     'data_table' => 
// );

// todo: re write the Storage::write() function

class Storage
{
    private static $config = array();

    public static function config($array)
    {
        self::$config = array_merge(self::$config, $array);
    }

    // public methods
    // write the file
    public static function write($fid, $file_name = null)
    {
        if (!$file = self::get($fid)) {
            return false;
        }
        if (isset($file_name)) {
            $dir = dirname($dir);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        } else {
            $file_name = dirname(__FILE__).'/'.$file['file_name'];
        }
        $fp = fopen($file_name, 'w');
        self::loadData($file['unique_id'], array('self', 'writeFile'), $fp);
        fclose($fp);

        return true;
    }

    // get the file raw data
    public static function readData($fid)
    {
        if (!$file = self::get($fid)) {
            return false;
        }
        $file_name = tempnam(sys_get_temp_dir(), 'FOO');
        if (!self::write($fid, $file_name)) {
            return false;
        }

        return file_get_contents($file_name);
    }
    // output data to the browser
    public static function OutputData($fid)
    {
        if (!$file = self::get($fid)) {
            return false;
        }
        $content_type = self::getContentType($file['ext']);
        header('Content-type: '.$content_type);
        header('Content-Length: '.$file['size']);
        self::loadData($file['unique_id'], array('self', 'flush'));
        exit;
    }
    // output data to the browser for downloading
    public static function OutputFile($fid)
    {
        if (!$file = self::get($fid)) {
            return false;
        }
        $content_type = self::getContentType($file['ext']);
        header('Content-type: '.$content_type);
        header('Content-Length: '.$file['size']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['file_name']).'"');
        self::loadData($file['unique_id'], array('self', 'flush'));
        exit;
    }
    // get a file
    public static function get($fid)
    {
        $db = self::$config['db'];
        $table = self::$config['index_table'];

        $sql = "SELECT * FROM `$table` WHERE id = ?";
        if (!$file = $db->getRow($sql, $fid)) {
            return array();
        }
        $file['size'] = substr($file['unique_id'], 32);
        $file_t = explode('.', $file['file_name']);
        $file['ext'] = strtolower(end($file_t));

        return $file;
    }
    // delete a file , it just delete from the index table
    public static function delete($fid)
    {
        $db = self::$config['db'];
        $table = self::$config['index_table'];

        $sql = "DELETE FROM `$table` WHERE id = ?";

        return $db->exec($sql, $fid);
    }
    // add a file -- you can use filename or string,if use string you must give the file_name
    public static function add($file, $file_name = null)
    {
        if (empty($file)) {
            return false;
        }

        $is_file = true;
        if (file_exists($file) && is_readable($file)) {
            isset($file_name) || $file_name = basename($file);
            $uid = self::getUidByFile($file);
        } else {
            if (!isset($file_name)) {
                throw new Exception("Error you must give the file_name!");
            }
            $uid = self::getUidByString($file);
            $is_file = false;
        }
        // allready has this file
        if ($id = self::checkIndex($uid, $file_name)) {
            return $id;
        }
        // insert index
        if (!$fid = self::insertIndex($uid, $file_name)) {
            return false;
        }
        // allready has same file data then return the fid directly
        if (self::checkMate($uid)) {
            return $fid;
        }
        // insert mate
        self::insertMate($uid);

        if ($is_file) {
            $fp = fopen($file, "r");
            $contents = '';
            $key = 0;
            while (!feof($fp)) {
                $data = fread($fp, 1024*500);
                self::insertData($uid, $data, $key++);
            }
            fclose($fp);
        } else {
            $array = str_split($file, 1024*500);
            foreach ($array as $key => $val) {
                self::insertData($uid, $val, $key++);
            }
        }

        return $fid;
    }

    // private methods
    private static function getUidByFile($file)
    {
        return md5_file($file).filesize($file);
    }

    private static function checkMate($uid)
    {
        $db = self::$config['db'];
        $table = self::$config['mate_table'];

        $sql = "SELECT unique_id FROM `$table` WHERE unique_id = ?";
        $uid = $db->getOne($sql, $uid);

        return $uid ? $uid : false;
    }

    private static function flush($data)
    {
        echo $data;
    }

    private static function writeFile($data, $fp)
    {
        fwrite($fp, $data);
    }

    private static function getContentType($ext)
    {
        $out = array();
        $array = file(dirname(__FILE__).'/mime.types');
        foreach ($array as $key => $val) {
            if ((!$val = trim($val)) || $val{0} == '#') {
                continue;
            }
            $s = preg_split('/\s+/', $val);
            if (!isset($s[1])) {
                continue;
            }
            $value = array_shift($s);
            foreach ($s as $v) {
                $out[$v] = $value;
            }
        }

        return isset($out[$ext]) ? $out[$ext] : 'application/octet-stream';
    }

    private static function checkIndex($uid, $file_name)
    {
        $db = self::$config['db'];
        $table = self::$config['index_table'];

        $sql = "SELECT id FROM `$table` WHERE unique_id = ? AND file_name = ?";
        $id = $db->getOne($sql, $uid, $file_name);

        return $id ? $id : false;
    }

    private static function insertMate($uid)
    {
        $db = self::$config['db'];
        $table = self::$config['mate_table'];

        $sql = "INSERT INTO `$table` (unique_id) VALUES (?)";
        $db->exec($sql, $uid);
    }

    private static function insertIndex($uid, $file_name)
    {
        $db = self::$config['db'];
        $table = self::$config['index_table'];

        $sql = "INSERT INTO `$table` (unique_id, file_name) VALUES (?, ?)";
        $db->exec($sql, $uid, $file_name);

        return $db->lastInsertId();
    }

    private static function loadData($uid, $callback, $param = array())
    {
        $db = self::$config['db'];
        $table = self::$config['data_table'];

        $sql = "SELECT data FROM `$table` WHERE unique_id = ? ORDER BY s_index ASC";
        $query = $db->query($sql, $uid);
        while ($rt = $db->fetch($query)) {
            call_user_func($callback, $rt['data'], $param);
        }
    }

    private static function getUidByString($data)
    {
        return md5($data).strlen($data);
    }

    private static function insertData($uid, $data, $index)
    {
        $db = self::$config['db'];
        $table = self::$config['data_table'];

        $sql = "INSERT INTO `$table` (unique_id, data, s_index) VALUES (?, ?, ?)";
        $db->exec($sql, $uid, $data, $index);
    }
}

?>