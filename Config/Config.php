<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Config.php
 */

namespace Karma;

use Karma\ArrayObject;
use Karma\Config\Exception;
use Karma\Config\Parser;
use Karma\Config\Writer;

class Config extends ArrayObject
{
    /**
     * @var \Karma\Config\Parser парсер конфигурации
     */
    protected $parser;

    /**
     * @var \Karma\Config\Writer объект сохранения конфигурации
     */
    protected $writer;

    /**
     * @var bool флаг определяющий доступность объекта к записи данных
     */
    protected $read_only = false;

    /**
     * @var string путь к кэш файлу
     */
    protected $cache;

    /**
     * конструктор
     * загружает файл конфигурации и определяет переменные объекта
     * @param \Karma\Config\Parser|string|array $config
     * @param array $options
     */
    public function __construct($config, array $options = array())
    {
        if($config instanceof Parser) {
            $this -> parser = $config;
            $config_data    = $config -> load();
        }
        else if(is_string($config) and is_file($config)) {
            $this -> parser = new Parser($config, $options);
            $config_data    = $this -> parser -> load();
        }
        else if(is_array($config)) {
            $config_data = $config;
        }
        else {
            throw new Exception('invalid_format', array(gettype($config)));
        }

        parent::__construct($config_data);

        if(isset($options['read_only']) and (bool) $options['read_only'] !== false) {
            $this -> readOnly();
        }
    }

    /**
     * сохранение переменной
     * если объект доступен только для чтения, выбрасываем исключение
     * @throws Config\Exception
     * @param string $name
     * @param mixed $value
     * @return Config
     */
    public function set($name, $value)
    {
        if($this -> read_only) {
            throw new Exception('read_only');
        }

        parent::set($name, $value);

        return $this;
    }

    /**
     * возвращает значение переменной
     * @param string $name
     * @param mixed|null $default
     * @return Config|mixed
     */
    public function get($name, $default = null)
    {
        $result = $default;

        if($this -> has($name)) {
            $result = parent::get($name, $default);

            if(is_array($result)) {
                $result = new self($result, array('read_only', $this -> read_only));
            }
        }

        return $result;
    }

    /**
     * возвращает флаг определяющий доступность объекта к записи
     * @return bool
     */
    public function isReadOnly()
    {
        return $this -> read_only;
    }

    /**
     * переводит объект в режим только для чтения
     * @return Config
     */
    public function readOnly()
    {
        foreach($this as $value) {
            if($value instanceof self) {
                $value -> readOnly();
            }
        }
        $this -> read_only = true;

        return $this;
    }

    /**
     * возвращает имя файла
     * @return string
     */
    public function getFileName()
    {
        return $this -> parser -> getFileName();
    }

    /**
     * возвращает разделитель секций
     * @return string
     */
    public function getSectionSeparator()
    {
        return $this -> parser -> getSectionSeparator();
    }

    /**
     * возвращает разделитель ключей
     * @return string
     */
    public function getKeySeparator()
    {
        return $this -> parser -> getKeySeparator();
    }

    /**
     * возвращает флаг определяющий парсинг секций
     * @return bool
     */
    public function isParseSections()
    {
        return $this -> parser -> isParseSections();
    }

    /**
     * возвращает список наследования
     * @return array
     */
    public function getExtends()
    {
        return $this -> parser -> getExtends();
    }

    /**
     * сохраняет текущий объект
     * @param string|null $file_name
     * @param array $options
     * @return void
     */
    public function save($file_name = null, array $options = array())
    {
        if($this -> writer === null) {
            $new_line       = isset($options['new_line']) ? $options['new_line'] : Writer::NEW_LINE;
            $this -> writer = new Writer($this, $new_line);
        }

        $this -> writer -> save($file_name, $options);
    }
}