<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Request.php
 */

namespace Karma\Http;

use Karma\Kernel;
use Karma\Http\Request\Parameters;

class Request
{
    /**
     * @var string строка запроса к серверу
     */
    protected $uri;

    /**
     * @var string строка зпроса к серверу без параметров
     */
    protected $url;

    /**
     * @var string метод зпроса к серверу
     */
    protected $method;

    /**
     * @var string имя браузера клиента
     */
    protected $user_agent;

    /**
     * @var string url траницы с которой перешёл клиент
     */
    protected $referer;

    /**
     * @var string ip адрес клиента
     */
    protected $client_ip;

    /**
     * @var \Karma\Http\Request\Parameters объект параметор запроса
     */
    public $parameters;

    /**
     * конструктор
     * определяет необходимые параметры
     */
    public function __construct()
    {
        if(Kernel::isCli()) {
            $arguments = Kernel::getCliArguments('uri', 'method', 'get', 'post');

            if(isset($arguments['uri'])) {
                $this -> uri = $arguments['uri'];
            }

            if(isset($arguments['method'])) {
                $this -> method = strtolower($arguments['method']);
            }

            if(isset($arguments['get'])) {
                parse_str($arguments['get'], $_GET);
            }

            if(isset($arguments['post'])) {
                parse_str($arguments['post'], $_POST);
            }
        }
        else {
            if(func_num_args() > 0) {
                $this -> uri = func_get_arg(0);
            }
            else if(isset($_GET['uri'])) {
                $this -> uri = $_GET['uri'];
                unset($_GET['uri']);
            }
            else {
                $this -> uri = $_SERVER['REQUEST_URI'];
            }

            if(func_num_args() > 1) {
                $this -> method = strtolower(func_get_arg(1));
            }
            else if(isset($_SERVER['REQUEST_METHOD'])) {
                $this -> method = strtolower($_SERVER['REQUEST_METHOD']);
            }
            else {
                $this -> method = 'get';
            }

            if(isset($_SERVER['HTTP_USER_AGENT'])) {
                $this -> user_agent = $_SERVER['HTTP_USER_AGENT'];
            }

            if(isset($_SERVER['HTTP_REFERER'])) {
                $this -> referer = $_SERVER['HTTP_REFERER'];
            }

            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this -> client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else if(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $this -> client_ip = $_SERVER['HTTP_CLIENT_IP'];
            }
            else if(isset($_SERVER['REMOTE_ADDR'])) {
                $this -> client_ip = $_SERVER['REMOTE_ADDR'];
            }
            else {
                $this -> client_ip = '0.0.0.0';
            }

            if($this -> method !== 'get' and $this -> method !== 'post') {
                parse_str(file_get_contents('php://input'), $_POST);
            }
        }

        $this -> parameters = new Parameters($_GET, $_POST);
        $this -> prepareUri();
    }

    /**
     * возвращает запос к серверу
     * @return string
     */
    public function getUri()
    {
        return $this -> uri;
    }

    /**
     * сохраняет запрос без учёта параметров
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this -> url = trim($url, '/');
    }

    /**
     * возвращает запрос без учёта параметров
     * @return string
     */
    public function getUrl()
    {
        return $this -> url;
    }

    /**
     * возвращает метод запроса к серверу
     * @return string
     */
    public function getMethod()
    {
        return $this -> method;
    }

    /**
     * возвращает имя браузера клиента
     * @return string
     */
    public function getUserAgent()
    {
        return $this -> user_agent;
    }

    /**
     * возвращает адрес страницы с которой был совершён переход
     * @return string
     */
    public function getReferer()
    {
        return $this -> referer;
    }

    /**
     * возвращает ip адес клиента
     * @return string
     */
    public function getClientIp()
    {
        return $this -> client_ip;
    }

    /**
     * возвращает флаг определющий защищённое соединение
     * @return bool
     */
    public function isHttps()
    {
        return (isset($_SERVER['HTTPS']) and filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * возвращает флаг определяющий ajax запрос
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    /**
     * поиск параметров
     * @param string $string
     * @param string $regexp
     * @return array|null
     */
    public function findParameters($string, $regexp)
    {
        $matches = array();
        $result  = null;
        $string  = trim($string, '/');

        if(preg_match('/^\s*(.*)\s*:\s*((?:[a-z_][a-z_0-9]*)\s*(?:,\s*[a-z_][a-z_0-9]*)*)\s*$/i', $regexp, $matches)) {
            $pattern   = trim($matches[1]);
            $variables = preg_split('/\s*,\s*/', trim($matches[2]));

            if(preg_match('#^' . $pattern . '$#', $string, $matches)) {
                $result = array();
                array_shift($matches);
                
                foreach($matches as $key => $value) {
                    if(isset($variables[$key])) {
                        $result[$variables[$key]] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * обработка строки запроса
     * @return void
     */
    protected function prepareUri()
    {
        $this -> uri = preg_replace('/\/+/', '/', $this -> uri);
        $this -> uri = preg_replace('/^\/(.*)\/?$/U', '\\1', $this -> uri);
        $this -> uri = preg_replace('/^(.*)\?.*$/U', '\\1', $this -> uri);
    }
}