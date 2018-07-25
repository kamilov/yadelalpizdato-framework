<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Lazy.php
 */

namespace Karma\Database\Orm;

class Lazy
{
    /**
     * @var string тип
     */
    private $type;

    /**
     * @var mixed значение
     */
    private $value;

    /**
     * @var callback обратная функция
     */
    private $callback;

    /**
     * конструктор
     * @param callback $callback
     * @param mixed $value
     * @param string $type
     */
    public function __construct($callback, $value, $type = 'callback')
    {
        $this -> type     = $type;
        $this -> value    = $value;
        $this -> callback = $callback;
    }

    /**
     * возвращает значение
     * @return mixed
     */
    public function getValue()
    {
        return $this -> value;
    }

    /**
     * вызов нужного метода
     * @param array $arguments
     * @return mixed
     */
    public function call(array $arguments = array())
    {
        $method = 'call' . ucfirst($this -> type);
        return call_user_func_array(array($this, $method), $arguments);
    }

    /**
     * возвращет результат выполнения указаного обработчика
     * @param array $arguments
     * @return mixed
     */
    public function callCallback(array $arguments)
    {
        return call_user_func_array($this -> callback, array_merge((array) $this -> value, $arguments));
    }
}