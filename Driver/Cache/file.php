<?php

/*
 * This file is part of the Geek-Zoo Projects.
 *
 * @copyright (c) 2010 Geek-Zoo Projects More info http://www.geek-zoo.com
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License
 * @author xuanyan <xuanyan@geek-zoo.com>
 *
 */

class fileWrapper extends CacheAbstract implements CacheWrapper
{
    private $path = null;

    function __construct($config)
    {
        $this->config = $config;
        if (!is_array($this->config)) {
            $this->path = $this->config;
        } else {
            $this->path = $this->config[0];
        }

        $this->now = time();
    }

    private function readFile($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        $data = require $filename;

        if ($data['timeout'] < $this->now) {
            return false;
        }

        return $data['data'];
    }

    private function writeFile($filename, $data, $expire)
    {
        $dir = dirname($filename);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        
        $data = array(
            'timeout' => $this->now+$expire,
            'data' => $data
        );

        $data = var_export($data, true);
        $tmp = <<<EOT
<?php
return $data;
?>
EOT;

        file_put_contents($filename, $tmp);
    }

    private function _delete($dir)
    {
        if (!file_exists($dir)) {
            return false;
        }

        if (is_file($dir)) {
            return unlink($dir);
        }

        $path = new DirectoryIterator($dir);
        foreach ($path as $val) {
            if (!$val->isDot()) {
                $this->_delete($val->getPathname());
            }
        }

        return rmdir($dir);
    }

    public function ns($key)
    {
        $this->ns = $key;

        return $this;
    }

    public function delete($key = null)
    {
        if ($this->ns) {
            if ($key === null) {
                $filename = $this->path.'/'.md5($this->ns);
            } else {
                $filename = $this->path.'/'.md5($this->ns).'/'.md5($key).'.php';
            }
            $this->ns = '';
        } else {
            $filename = $this->path.'/'.md5($key).'.php';
        }

        return $this->_delete($filename);
    }

    public function get($key)
    {
        if ($this->ns) {
            $filename = $this->path.'/'.md5($this->ns).'/'.md5($key).'.php';
            $this->ns = '';
        } else {
            $filename = $this->path.'/'.md5($key).'.php';
        }

        return $this->readFile($filename);
    }

    public function set($key, $value, $expire = 3600)
    {
        if ($this->ns) {
            $filename = $this->path.'/'.md5($this->ns).'/'.md5($key).'.php';
            $this->ns = '';
        } else {
            $filename = $this->path.'/'.md5($key).'.php';
        }
        $this->writeFile($filename, $value, $expire);

        return true;
    }
}


?>