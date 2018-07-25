<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Functions.php
 */

namespace Karma\Database\Builder;

use Karma\Database;

class Functions
{
    /**
     * @var string имя функции
     */
    protected $name;

    /**
     * @var array список аргументов
     */
    protected $arguments;

    /**
     * @var string|null зеркало для имени
     */
    protected $alias;

    /**
     * конструктор
     * @param string $name
     * @param array $arguments
     */
    public function __construct($name, array $arguments)
    {
        $this -> name      = $name;
        $this -> arguments = $arguments;
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this -> compile(true);
        }
        catch (\Exception $exception) {
            die(Exception::text($exception));
        }
    }

    /**
     * метод обрабатывающий вызов объекта как функции
     * @param string $name
     * @param array $arguments
     * @return Functions
     */
    public function __call($name, $arguments)
    {
        return new Functions($name, array_merge(array($this -> compile(), $arguments)));
    }

    /**
     * возвращает имя функции
     * @return string
     */
    public function getName()
    {
        return $this -> name . '()';
    }

    /**
     * сохраняет зеркало для выбора данных
     * @param string $alias
     * @return Functions
     */
    public function setAlias($alias)
    {
        $this -> alias = $alias;
        return $this;
    }

    /**
     * возвращает имя зеркала функции
     * @param bool $quote
     * @return null|string
     */
    public function getAlias($quote = true)
    {
        return $quote ? Database::quoteName($this -> alias) : $this -> alias;
    }

    /**
     * компиляция строки
     * @param bool $alias
     * @return string
     */
    public function compile($alias = false)
    {
        return $this -> name . '( ' . implode(', ', $this -> arguments) . ' )' . (($alias and $this -> alias !== null) ? ' as ' . $this -> getAlias() : '');
    }
}