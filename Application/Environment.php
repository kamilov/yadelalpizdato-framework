<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Environment.php
 */

namespace Karma\Application;

use Karma\Config;

class Environment
{
    /**
     * @var \Karma\Config объект конфигурации приложения
     */
    protected $config;

    /**
     * @var array список директорий приложения
     */
    protected $directories = array(
         'controllers', 'languages', 'mappers', 'models', 'views',
    );

    /**
     * @var array список установленных языковых версий
     */
    protected $languages = array();

    /**
     * конструктор
     * сохранение конфигурации и определение нужных параметров
     * @param Config $config
     * @param array $options
     */
    public function __construct($config, array $options = array())
    {
        if($config instanceof Config) {
            $this -> config = $config;
        }
        else {
            $this -> config = new Config($config, $options);
        }

        $this -> findDirectories();
        $this -> findLanguages();
    }

    /**
     * возвращает объект конфигурации
     * @return \Karma\Config
     */
    public function getConfig()
    {
        return $this -> config;
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
     * возвращает путь к указанной директории приложения
     * @throws Exception
     * @param string $name
     * @param bool $check
     * @return string
     */
    public function getDirectoryPath($name, $check = false)
    {
        $parts  = explode('.', $name, 2);

        if(!isset($this -> directories[$parts[0]])) {
            throw new Exception('child_not_found', array($parts[0]));
        }

        $result = $this -> directories[$parts[0]];

        if(isset($parts[1])) {
            $result .= DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $parts[1]);
        }

        if($check and !is_dir($result)) {
            throw new Exception('directory_not_found', array(str_replace('.', '/', $name)));
        }

        return $result;
    }

    /**
     * поиск и определение директорий приложения
     * @throws Exception
     * @return void
     */
    protected function findDirectories()
    {
        $names = $this -> directories;
        $this -> directories = array();

        if(($path = $this -> getConfig() -> path) !== null) {
            $not_find = array();

            foreach($names as $name) {
                if($path -> has($name)) {
                    $directory = rtrim($path -> get($name), '\\/');

                    if(!is_dir($directory)) {
                        throw new Exception('directory_not_defined', array($name));
                    }

                    $this -> directories[$name] = $directory;
                    array_push($not_find, $path);
                }
            }

            $names = array_diff($names, $not_find);

            if(count($names) === 0) {
                return;
            }

            $application = rtrim($path -> application, '\\/');

            foreach($names as $name) {
                $directory = $path -> get($name, ($application . DIRECTORY_SEPARATOR . $name));

                if(!is_dir($directory)) {
                    throw new Exception('directory_not_defined', array($name));
                }

                $this -> directories[$name] = $directory;
            }
        }
        else {
            $application = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'application';

            if(!is_dir($application)) {
                throw new Exception('default_not_found', array($application));
            }

            foreach($names as $name) {
                $directory = $application . DIRECTORY_SEPARATOR . $name;

                if(!is_dir($directory)) {
                    throw new Exception('directory_not_defined', array($name));
                }

                $this -> directories[$name] = $directory;
            }
        }
    }

    /**
     * поиск языковых версий
     * @return void
     */
    protected function findLanguages()
    {
        foreach(glob($this -> getDirectoryPath('languages.*.language') . '.info') as $file_name) {
            if(($data = @parse_ini_file($file_name, false)) !== false) {
                $directory     = dirname($file_name);
                $language_name = basename(dirname($file_name));
                $this -> languages[$language_name] = array_merge($data, array('directory' => $directory));
            }
        }
    }
}