<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Memcache.php
 */

namespace Karma\Cache;

use Karma\Cache;

class Memcache implements Cache
{
    /**
     * @var \Memcache объект кэша
     */
    protected $memcache;

    public function __construct($config)
    {
        if(!class_exists('Memcache')) {
            throw new Exception('extension_not_enabled', array('Memcache'));
        }

        $config = array_merge(array(
            'host' => 'localhost',
            'port' => 11211,
            'persistent' => true,
            'weight' => 1,
            'timeout' => 1,
            'retry' => 15,
            'status' => true,
            'tres_hold' => 20000,
            'savings' => 0.2
        ), $config);

        $memcache = new \Memcache();
        $memcache -> addserver($config['host'], $config['port'], $config['persistent'], $config['weight'], $config['timeout'], $config['retry'], $config['status'], array($this, 'callback'));
        $memcache -> setcompressthreshold($config['tres_hold'], $config['savings']);
        $this -> memcache = $memcache;
    }

    /**
     * вощвращает объект кэша
     * @return \Memcache
     */
    public function getMemcache()
    {
        return $this -> memcache;
    }

    public function set($name, $value, $expire = null)
    {
        $expire = $expire === null ? self::EXPIRE : $expire;

        try {
            return $this -> memcache -> set($name, $value, null, $expire);
        }
        catch(Exception $exception) {
            return false;
        }
    }

    public function get($name)
    {
        try {
            $result = $this -> memcache -> get($name);
            return $result === false ? null : $result;
        }
        catch(Exception $exception) {
            return null;
        }
    }

    public function delete($name)
    {
        try {
            return $this -> memcache -> delete($name);
        }
        catch(Exception $exception) {
            return false;
        }
    }

    /**
     * обработка ошибки сервера
     * @throws exception
     * @param string $host
     * @param int $port
     * @return void
     */
    public function callback($host, $port)
    {
        throw new Exception('memcache error "' . $host . ':' . $port . '"');
    }
}