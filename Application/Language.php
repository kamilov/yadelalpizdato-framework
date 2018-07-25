<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Language.php
 */

namespace Karma\Application;

use Karma\ArrayObject;

class Language extends ArrayObject
{
    /**
     * @var array список языковых версий
     */
    protected $languages = array();

    /**
     * @var string текущая языковая версия
     */
    protected $current;

    /**
     * конструктор
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        if($environment -> getConfig() -> has('language') === false) {
            return;
        }

        $config = clone $environment -> getConfig() -> language;
        $all    = $environment -> getLanguages();

        foreach($config -> get('list', array()) as $name) {
            if(!isset($all[$name])) {
                throw new Exception('language_not_installed', array($name));
            }
            $this -> languages[$name] = $all[$name];
        }

        $this -> current = $config -> get('default', reset(array_keys($this -> languages)));
    }

    /**
     * возвращает имя активной языковой версии
     * @return string
     */
    public function getCurrent()
    {
        return $this -> current;
    }

    /**
     * возвращает список языковых версий
     * @return array
     */
    public function getLanguages()
    {
        return $this -> languages;
    }

    /**
     * устанавливает актуальную языкоую версию
     * @param $name
     * @return void
     */
    public function setLanguage($name)
    {
        if(!isset($this -> languages[$name])) {
            throw new Exception('language_not_installed', array($name));
        }
        $this -> current = $name;
    }

    /**
     * проверка наличия языковой версии
     * @param string $name
     * @return bool
     */
    public function hasLanguage($name)
    {
        return isset($this -> languages[$name]);
    }

    /**
     * загруза файла языковой версии
     * @throws Exception
     * @param string $name
     * @return Language
     */
    public function load($name)
    {
        if(!preg_match('/^[a-z_0-9]+$/i', $name)) {
            throw new Exception('language_name_invalid', array($name));
        }

        $file_name = $this -> languages[$this -> current]['directory'] . DIRECTORY_SEPARATOR . $name . '.lng';

        if(!is_file($file_name)) {
            throw new Exception('language_file_not_found', array($this -> current, $name));
        }
        
        if(($data = @parse_ini_file($file_name, true)) === false) {
            throw new Exception('file_not_readable', array(($this -> current . '/' . $name . '.lng')));
        }

        $this -> add(array($name => $data));

        return $this;
    }

    /**
     * возвращает флаг определяющий наличие хоть одной языковой версии
     * @return bool
     */
    public function isEnable()
    {
        return count($this -> languages) > 0;
    }

    /**
     * возвращает флаг определяющий наличие нескольких языковых версий
     * @return bool
     */
    public function isMultiLanguage()
    {
        return count($this -> getLanguages()) > 1;
    }
}