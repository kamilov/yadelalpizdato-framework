<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Writer.php
 */

namespace Karma\Config;

use Karma\Config;

class Writer
{
    /**
     * перенос строки по умолчанию
     */
    const NEW_LINE = "\n";

    /**
     * шаблон для наследования
     */
    const TEMPLATE_EXTENDS = '%s %s %s';

    /**
     * шаблон секции
     */
    const TEMPLATE_SECTION = '[ %s ]';

    /**
     * шаблон ключей
     */
    const TEMPLATE_KEYS    = '%s = %s';

    /**
     * @var \Karma\Config объект конфигурации
     */
    protected $config;

    /**
     * @var string перенос строки
     */
    protected $new_line;

    /**
     * конструктор
     * @param \Karma\Config $config
     * @param string $new_line
     */
    public function __construct(Config $config, $new_line = self::NEW_LINE)
    {
        $this -> config   = $config;
        $this -> new_line = $new_line;
    }

    /**
     * сохраняет конфигурацию в файл
     * @throws Exception
     * @param string|null $file_name
     * @param array $options
     * @return void
     */
    public function save($file_name = null, array $options = array())
    {
        if($file_name === null) {
            $file_name = $this -> config -> getFileName();
        }

        if(!is_file($file_name)) {
            $directory = dirname(__FILE__);

            if(!is_dir($directory)) {
                if(!@mkdir($directory, 0777, true)) {
                    throw new Exception('directory_create_error', array($directory));
                }
            }

            unset($directory);
        }

        extract($options);

        if(!isset($section_separator)) {
            $section_separator = $this -> config -> getSectionSeparator();
        }

        if(!isset($key_separator)) {
            $key_separator = $this -> config -> getKeySeparator();
        }

        if(!isset($parse_sections)) {
            $parse_sections = $this -> config -> isParseSections();
        }

        $extends   = $this -> config -> getExtends();
        $load_data = $this -> config -> toArray();
        $file_data = array();

        foreach($load_data as $name => $value) {
            if((bool) $parse_sections === true) {
                if(isset($extends[$name]) and isset($load_data[$extends[$name]])) {
                    $value = $this -> getDifference($value, $load_data[$extends[$name]]);
                    $name  = sprintf(self::TEMPLATE_EXTENDS, $name, $section_separator, $extends[$name]);
                }

                if(is_array($value)) {
                    $value = $this -> parseKey($value, $key_separator);
                }
                else {
                    $value = $this -> prepareValue($value);
                }

                array_push(
                    $file_data,
                    sprintf(self::TEMPLATE_SECTION, $name),
                    $value
                );
            }
            else {
                array_push(
                    $file_data,
                    $this -> parseKey(array($name => $value), $key_separator),
                    $this -> new_line
                );
            }
        }

        if(!@file_put_contents($file_name, implode($this -> new_line, $file_data))) {
            throw new Exception('file_write_error', array($file_name));
        }
    }

    /**
     * перевод массива в строку
     * @param array $data
     * @param string $key_separator
     * @param array $parents
     * @return string
     */
    protected function parseKey(array $data, $key_separator, array $parents = array())
    {
        $result = '';

        foreach($data as $key => $value) {
            $group = array_merge($parents, array($key));

            if(is_array($value)) {
                $result .= $this -> parseKey($value, $key_separator, $group);
            }
            else {
                $result .= sprintf(self::TEMPLATE_KEYS, implode($key_separator, $group), $this -> prepareValue($value) . $this -> new_line);
            }
        }

        return $result;
    }

    /**
     * обработка значения переменной
     * @throws Exception
     * @param string $value
     * @return string
     */
    protected function prepareValue($value)
    {
        if(is_integer($value) or is_float($value)) {
            return $value;
        }
        else if(is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        else if((strpos($value, '"') !== false and strpos($value, '\'') === false) or (strpos($value, '"') === false and strpos($value, '\'') === false)) {
            return '\'' . $value . '\'';
        }
        else if(strpos($value, '\'') !== false and strpos($value, '"') === false) {
            return '"' . $value . '"';
        }
        else {
            throw new Exception('invalid_value_format', array(gettype($value)));
        }
    }

    /**
     * ищет в первом массиве схожие элементы со вторым и удаляет их
     * @param array $first
     * @param array $second
     * @return array
     */
    protected function getDifference($first, $second)
    {
        foreach($second as $key => $value) {
            if(isset($first[$key])) {
                if($first[$key] === $value) {
                    unset($first[$key]);
                }
                else if(is_array($first[$key]) and is_array($value)) {
                    $first[$key] = $this -> getDifference($first[$key], $value);
                }
            }
        }
        return $first;
    }
}