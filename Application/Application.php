<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Application.php
 */

namespace Karma;

use Karma\Database;
use Karma\Application\Environment;
use Karma\Application\Exception;
use Karma\Application\Language;
use Karma\Database\Orm;

class Application
{
    /**
     * @var \Karma\Application\Environment объект рабочей среды приложения
     */
    protected $environment;

    /**
     * @var \Karma\Application\Language объект транслитезации приложенияы
     */
    protected $language;

    /**
     * @var \Karma\Router объкт маршртизации
     */
    protected $router;

    /**
     * @var \Karma\Database\Schema объект для работы с базой данных
     */
    protected $database;

    /**
     * конструктор
     * @param Environment|mixed $environment
     * @param array $options
     */
    public function __construct($environment, array $options = array())
    {
        if($environment instanceof Environment) {
            $this -> environment = $environment;
        }
        else {
            $this -> environment = new Environment($environment, $options);
        }

        if($this -> getConfig() -> has('database')) {
            Database::setConfiguration($this -> getConfig() -> database -> toArray());
        }

        Orm::setMapperDirectory($this -> getDirectoryPath('mappers'));
        Orm::setModelDirectory($this -> getDirectoryPath('models'));
    }

    /**
     * возвращает объект конфигурации
     * @return \Karma\Config
     */
    public function getConfig()
    {
        return $this -> environment -> getConfig();
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
        return $this -> environment -> getDirectoryPath($name, $check);
    }

    /**
     * озвращает объект транслитезации приложения
     * @return Application\Language
     */
    public function getLanguage()
    {
        if($this -> language === null) {
            $this -> language = new Language($this -> environment);
        }
        return $this -> language;
    }

    /**
     * озвращает объект маршрутизации
     * @return Router
     */
    public function getRouter()
    {
        if($this -> router === null) {
            $this -> router = new Router($this);
        }
        return $this -> router;
    }

    /**
     * возвращает ответ сервера
     * @return \Karma\Http\Response
     */
    public function getResponse()
    {
        return $this -> getRouter() -> execution();
    }

    /**
     * возвращает объект для работы с базой данных
     * @param string|null $name
     * @return Database\Schema
     */
    public function getDatabase($name = null)
    {
        if($name !== null) {
            $this -> database = Database::getConnection($name);
        }
        else if($this -> database === null) {
            $this -> database = Database::current();
        }

        return $this -> database;
    }
}