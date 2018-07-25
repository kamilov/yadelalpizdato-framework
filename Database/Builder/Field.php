<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Field.php
 */

namespace Karma\Database\Builder;

use Karma\Database;

class Field
{
    /**
     * @var string имя поля
     */
    protected $name;

    /**
     * @var string|null префикс
     */
    protected $prefix;

    /**
     * @var string|null зеркало имени поля
     */
    protected $alias;

    /**
     * конструктор
     * @param string $name
     * @param string|null $prefix
     */
    public function __construct($name, $prefix = null)
    {
        $this -> name   = $name;
        $this -> prefix = $prefix;
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this -> compile();
        }
        catch (\Exception $exception) {
            die(Exception::text($exception));
        }
    }

    /**
     * метод обработки вызова объекта как функции
     * @param string $name
     * @param array $arguments
     * @return Functions
     */
    public function __call($name, $arguments)
    {
        return new Functions($name, array_merge(array($this -> compile()), $arguments));
    }

    /**
     * возвращает имя поля
     * @return string
     */
    public function getName()
    {
        return $this -> name;
    }

    /**
     * сохраняет префикс
     * @param string $prefix
     * @return Field
     */
    public function setPrefix($prefix)
    {
        $this -> prefix = $prefix;
        return $this;
    }

    /**
     * возвращает префикс
     * @return null|string
     */
    public function getPrefix()
    {
        return $this -> prefix;
    }

    /**
     * возвращает алиас имени поля
     * @param string $alias
     * @return Field
     */
    public function setAlias($alias)
    {
        $this -> alias = $alias;
        return $this;
    }

    /**
     * возвращает алиас
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
        $result = '';

        if($this -> prefix !== null and strlen($this -> prefix) > 0) {
            $result .= Database::quoteName($this -> prefix) . '.';
        }

        $result .= Database::quoteName($this -> name);

        if($alias and $this -> alias !== null) {
            $result .= ' as ' . Database::quoteName($this -> alias);
        }

        return $result;
    }
}