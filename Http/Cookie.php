<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Cookie.php
 */

namespace Karma\Http;

class Cookie
{
    /**
     * @var string имя куки
     */
    protected $name;

    /**
     * @var string значение куки
     */
    protected $value;

    /**
     * @var int время жизни куки
     */
    protected $expire;

    /**
     * @var string путь установки куки
     */
    protected $path;

    /**
     * @var string|null домен установки куки
     */
    protected $domain;

    /**
     * @var bool флаг определяющий установку куки для защищённого соединеия
     */
    protected $secure;

    /**
     * @var bool флаг определяющий установки и изменение куки только через http
     */
    protected $http_only;

    /**
     * конструктор
     * @throws Exception
     * @param string $name
     * @param string $value
     * @param integer $expire
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $http_only
     */
    public function __construct($name, $value, $expire = 3600, $path = '/', $domain = null, $secure = false, $http_only = true)
    {
        if(preg_match('/[=,; \t\r\n\013\014]/', $name)) {
            throw new Exception('invalid_name');
        }

        if(preg_match('/[=,; \t\r\n\013\014]/', $value)) {
            throw new Exception('invalid_value');
        }

        $this -> name      = $name;
        $this -> value     = $value;
        $this -> expire    = (int) $expire;
        $this -> path      = $path;
        $this -> domain    = $domain;
        $this -> secure    = (bool) $secure;
        $this -> http_only = (bool) $http_only;
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        $result = urlencode($this -> getName()) . '=';

        if ((string) $this -> getValue() === '') {
            $result .= 'deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001);
        } else {
            $result .= urlencode($this -> getValue());

            if ($this -> getExpire() !== 0) {
                $result .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $this->getExpire());
            }
        }

        if ($this -> getPath() !== null) {
            $result .= '; path=' . $this -> getPath();
        }

        if ($this -> getDomain() !== null) {
            $result .= '; domain=' . $this -> getDomain();
        }

        if ($this -> isSecure()) {
            $result .= '; secure';
        }

        if ($this -> isHttpOnly()) {
            $result .= '; httponly';
        }

        return $result;
    }

    /**
     * возвращает имя куки
     * @return string
     */
    public function getName()
    {
        return $this -> name;
    }

    /**
     * возвращает значение куки
     * @return string
     */
    public function getValue()
    {
        return $this -> value;
    }

    /**
     * возвращет время жизни куки
     * @return int
     */
    public function getExpire()
    {
        return $this -> expire;
    }

    /**
     * возвращает путь установки куки
     * @return string
     */
    public function getPath()
    {
        return $this -> path;
    }

    /**
     * возвращает домен на который следует установить куки
     * @return null|string
     */
    public function getDomain()
    {
        return $this -> domain;
    }

    /**
     * возвращает флаг определяющий установку кукис для защищённого соединения
     * @return bool
     */
    public function isSecure()
    {
        return $this -> secure;
    }

    /**
     * возвращает флаг определяющий установку или изменения куи только через http
     * @return bool
     */
    public function isHttpOnly()
    {
        return $this -> http_only;
    }
}