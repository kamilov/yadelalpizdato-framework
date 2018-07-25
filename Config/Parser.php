<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Parser.php
 */

namespace Karma\Config;

class Parser
{
    /**
     * разделитель секций по умолчанию
     */
    const SECTION_SEPARATOR = ':';

    /**
     * разделитель ключей
     */
    const KEY_SEPARATOR = '.';

    /**
     * флаг определяющий обработку секций по умлчанию
     */
    const PARSE_SECTIONS = true;

    /**
     * @var string разделитель секций
     */
    protected $section_separator;

    /**
     * @var string разделитель ключей
     */
    protected $key_separator;

    /**
     * @var bool флаг определяющий обработку секций
     */
    protected $parse_sections;

    /**
     * @var string имя файла
     */
    protected $file_name;

    /**
     * @var array список секций для загрузки
     */
    protected $sections_load;

    /**
     * @var array список загруженых секций
     */
    protected $sections_extends = array();

    /**
     * @var array список переменых переданных в файл
     */
    protected $variables;

    /**
     * конструктор
     * @throws Exception
     * @param string $file_name
     * @param array $options
     */
    public function __construct($file_name, $options = array())
    {
        if(!is_file($file_name)) {
            throw new Exception('file_not_found', array($file_name));
        }
        else if(!is_readable($file_name)) {
            throw new Exception('file_not_readable', array($file_name));
        }
        else {
            $this -> file_name = $file_name;
        }

        if(isset($options['parse_sections'])) {
            $this -> parse_sections = (bool) $options['parse_sections'];
        }
        else {
            $this -> parse_sections = self::PARSE_SECTIONS;
        }

        if(isset($options['section_separator'])) {
            $this -> section_separator = (string) $options['section_separator'];
        }
        else {
            $this -> section_separator = self::SECTION_SEPARATOR;
        }

        if(isset($options['key_separator'])) {
            $this -> key_separator = (string) $options['key_separator'];
        }
        else {
            $this -> key_separator = self::KEY_SEPARATOR;
        }

        if(isset($options['sections'])) {
            $this -> sections_load = $options['sections'];
        }

        if(isset($options['variables'])) {
            $this -> variables = array(
                'original' => $options['variables'],
                'extract'  => $options['variables']
            );
        }
        else {
            $this -> variables = array(
                'original' => array(),
                'extract'  => array()
            );
        }
    }

    /**
     * загрузка и обработка содержимого конфигурационного файла
     * @throws Exception
     * @return array
     */
    public function load()
    {
        try {
            $file_data = parse_ini_file($this -> getFileName(), $this -> isParseSections());
            $load_data = array();
            $result    = array();

            if($this -> isParseSections() === true) {
                foreach($file_data as $name => $value) {
                    $parts = explode($this -> getSectionSeparator(), $name);
                    $name  = trim($parts[0]);

                    switch(count($parts)) {
                        case 1 :
                            $load_data[$name] = $value;
                        break;

                        case 2 :
                            $load_data[$name] = array_merge(array(';extends' => trim($parts[1])), $value);
                        break;

                        default :
                            throw new Exception('extends_limit', array($name));
                        break;
                    }
                }
            }
            else {
                $load_data = $file_data;
            }

            unset($file_data);

            if($this -> getLoaded() === null) {
                foreach($load_data as $name => $value) {
                    if(is_array($value)) {
                        $result[$name] = $this -> parseSection($name, $load_data);
                    }
                    else {
                        $result = $this -> merge($result, $this -> parseKey($name, $value));
                        $this -> setVariables($result);
                    }
                }
            }
            else {
                foreach((array) $this -> getLoaded() as $name) {
                    if(!isset($load_data[$name])) {
                        throw new Exception('section_not_found', array($name));
                    }
                    $result = $this -> merge($this -> parseSection($name, $load_data), $result);
                    $this -> setVariables($result);
                }
            }

            $this -> resetVariables();

            return $result;
        }
        catch(Exception $exception) {
            $exception -> setFile($this -> getFileName());
            $exception -> setLine(0);
            throw $exception;
        }
    }

    /**
     * возвращает имя файла
     * @return string
     */
    public function getFileName()
    {
        return $this -> file_name;
    }

    /**
     * возвращает разделитель секций
     * @return string
     */
    public function getSectionSeparator()
    {
        return $this -> section_separator;
    }

    /**
     * возвращает разделитель ключей
     * @return string
     */
    public function getKeySeparator()
    {
        return $this -> key_separator;
    }

    /**
     * возвращает флаг определяющий парсинг секций
     * @return bool
     */
    public function isParseSections()
    {
        return $this -> parse_sections;
    }

    public function getLoaded()
    {
        return $this -> sections_load;
    }

    /**
     * возвращает список наследования
     * @return array
     */
    public function getExtends()
    {
        return $this -> sections_extends;
    }

    /**
     * обработка секций
     * @throws Exception
     * @param string $name
     * @param array $data
     * @param array $result
     * @return array
     */
    protected function parseSection($name, array $data, array $result = array())
    {
        $current = $data[$name];

        $this -> resetVariables();

        foreach($current as $key => $value) {
            if($key === ';extends') {
                if(!isset($data[$value])) {
                    throw new Exception('section_not_found', array($value));
                }

                $this -> validExtends($name, $value);
                $result = $this -> parseSection($value, $data, $result);
            }
            else {
                $result = $this -> parseKey($key, $value, $result);
                $this -> setVariables($result);
            }
        }

        return $result;
    }

    /**
     * обработка ключей
     * @throws Exception
     * @param string $key
     * @param string $value
     * @param array $result
     * @return array
     */
    public function parseKey($key, $value, array $result = array())
    {
        if(strpos($key, $this -> getKeySeparator()) !== false) {
            $parts = explode($this -> getKeySeparator(), $key, 2);

            if(strlen($parts[0]) > 0 and strlen($parts[1]) > 0) {
                if(!isset($result[$parts[0]])) {
                    if($parts[0] === '0' and !empty($result)) {
                        $result = array($parts[0] = $result);
                    }
                    else {
                        $result[$parts[0]] = array();
                    }
                }
                else if(!is_array($result[$parts[0]])) {
                    throw new Exception('key_is_not_array', array($parts[0]));
                }

                $result[$parts[0]] = $this -> parseKey($parts[1], $value, $result[$parts[0]]);
            }
        }
        else {
            $result[$key] = preg_replace_callback('/\{\s*\$([a-z_0-9]+(?:\.[a-z_0-9]+)*)\s*\}/i', array($this, 'getVariables'), $value);
        }

        return $result;
    }

    /**
     * сливание двух массивов
     * @param array $first
     * @param array $second
     * @return array
     */
    protected function merge($first, $second)
    {
        if(is_array($first) and is_array($second)) {
            foreach($second as $key => $value) {
                if(isset($first[$key])) {
                    $first[$key] = $this -> merge($first[$key], $value);
                }
                else if($key === 0) {
                    $first = array(0 => $this -> merge($first, $value));
                }
                else {
                    $first[$key] = $value;
                }
            }
        }
        else {
            $first = $second;
        }

        return $first;
    }

    /**
     * проверка кругового наследования секций
     * @throws Exception
     * @param string $extending
     * @param string $extended
     * @return void
     */
    protected function validExtends($extending, $extended)
    {
        $current = $extended;

        while(array_key_exists($current, $this -> getExtends())) {
            if($this -> sections_extends[$current] == $extending) {
                throw new Exception('cyclic_extends');
            }
            $current = $this -> sections_extends[$current];
        }

        $this -> sections_extends[$extending] = $extended;
    }

    /**
     * сохранение переменных в общем списке
     * @param array $variables
     * @return void
     */
    protected function setVariables(array $variables)
    {
        $this -> variables['extract'] = array_merge($this -> variables['extract'], $variables);
    }

    /**
     * возвращает значние нужной переменной
     * @param array $matches
     * @return mixed
     */
    protected function getVariables(array $matches)
    {
        $parts  = explode('.', $matches[1]);
        $result = $this -> variables['extract'];

        foreach($parts as $part) {
            if(isset($result[$part])) {
                $result = $result[$part];
            }
            else {
                return $matches[0];
            }
        }

        return $result;
    }

    /**
     * обнуление переменных
     * @return void
     */
    protected function resetVariables()
    {
        $this -> variables['extract'] = $this -> variables['original'];
    }
}