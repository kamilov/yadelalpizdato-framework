<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Parameters.php
 */

namespace Karma\Http\Request;

class Parameters
{
    /**
     * @var array список параметров ереданных методом get
     */
    protected $get = array();

    /**
     * @var array список параметров переданных методом post
     */
    protected $post = array();

    /**
     * @var array список параметров извлечённых из строки запроса
     */
    protected $parameters = array();

    /**
     * конструктор
     * @param array $get
     * @param array $post
     */
    public function __construct(array $get = array(), array $post = array())
    {
        $this -> get  = $get;
        $this -> post = $post;
    }

    /**
     * возвращает get параметры
     * @param string|null $name
     * @return array|null|mixed
     */
    public function get($name = null)
    {
        if($name === null) {
            return $this -> get;
        }
        return isset($this -> get[$name]) ? $this -> get[$name] : null;
    }

    /**
     * возвращает post параметры
     * @param string|null $name
     * @return array|null|mixed
     */
    public function post($name = null)
    {
        if($name === null) {
            return $this -> post;
        }
        return isset($this -> post[$name]) ? $this -> post[$name] : null;
    }

    /**
     * возвращает или сохраняет список параметров извлечённых из строки запроса
     * @param string|array|null $name
     * @return array|null|mixed
     */
    public function parameters($name = null)
    {
        if(is_array($name)) {
            $this -> parameters    = array_merge($this -> parameters, $name);
            $GLOBALS['parameters'] = $this -> parameters;
            return $this;
        }
        else if($name === null) {
            return $this -> parameters;
        }
        
        return isset($this -> parameters[$name]) ? $this -> parameters[$name] : null;
    }
}