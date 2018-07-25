<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Schema.php
 */

namespace Karma\Database;

use Karma\Kernel;
use Karma\Database;
use Karma\Database\Schema\Exception;

abstract class Schema
{
    /**
     * @var resource ссылка на соединение с базой данных
     */
    protected $link;

    /**
     * @var array история выполненых запросов
     */
    protected $history = array();

    /**
     * @var string трока текущего запроса
     */
    protected $query;

    /**
     * @var resource текущий результат выполненного запроса
     */
    protected $result;

    /**
     * @var array|null список аргументов передаваемых в строку запроса
     */
    protected $arguments;

    /**
     * @var string мя текущей базы данных
     */
    protected $base_name;

    /**
     * @var bool флаг определяющий вывод отладочной информации
     */
    protected $debug = false;

    /**
     * @var string|null префикс таблиц базы данных
     */
    protected $prefix;

    /**
     * конструктор
     * @throws Schema\Exception
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $base
     * @param string|null $prefix
     */
    public function __construct($host, $user, $password, $base, $prefix = null)
    {
        if(($this -> link = $this -> connect($host, $user, $password)) === false) {
            throw new Exception('can_not_connect', array($host, $user));
        }

        $this -> setBase($base, $prefix);
    }

    /**
     * метод выбора базы данных для соединения
     * @throws Schema\Exception
     * @param string $base
     * @param string|null $prefix
     * @return Schema
     */
    public function setBase($base, $prefix = null)
    {
        if($this -> select($base) === false) {
            throw new Exception('base_not_found', array($base));
        }
        $this -> base_name = $base;
        $this -> prefix    = $prefix;
        return $this;
    }

    /**
     * возвращает имя текущей базы данных
     * @return string
     */
    public function getBase()
    {
        return $this -> base_name;
    }

    /**
     * возвращает префикс таблиц
     * @return string|null
     */
    public function getPrefix()
    {
        return $this -> prefix;
    }

    /**
     * сохраняет сроку запроса
     * @throws Schema\Exception
     * @param string|object $query
     * @return Schema
     */
    public function setQuery($query)
    {
        if(!is_string($query) and (is_object($query) and !is_callable(array($query, '__toString')))) {
            throw new Exception('query_not_string');
        }
        $this -> query = (string) $query;
        return $this;
    }

    /**
     * возвращает строку запроса
     * @return string
     */
    public function getQuery()
    {
        return $this -> query;
    }

    /**
     * соханяет результат запроса к базе данных
     * @throws Schema\Exception
     * @param string $result
     * @return Schema
     */
    public function setResult($result)
    {
        if(!is_resource($result)) {
            throw new Exception('result_invalid_format', array(gettype($result)));
        }
        $this -> result = $result;
        return $this;
    }

    /**
     * соханение списка аргументов передаваемых в строку запроса
     * @param array|string $arguments
     * @return Schema
     */
    public function arguments($arguments)
    {
        if(is_array($arguments) and Kernel::arrayKeysIsInt($arguments) === false) {
            $this -> arguments = $arguments;
        }
        else {
            $this -> arguments = func_get_args();
        }
        return $this;
    }

    /**
     * включение вывода отладочной информации
     * @return Schema
     */
    public function enableDebug()
    {
        $this -> debug = true;
        return $this;
    }

    /**
     * отключение вывода отладочной информации
     * @return Schema
     */
    public function disableDebug()
    {
        $this -> debug = false;
        return $this;
    }

    /**
     * озвращает историю запросов
     * @return array
     */
    public function getHistory()
    {
        return $this -> history;
    }

    /**
     * возвращает колличество выполненых запросов
     * @return int
     */
    public function getCountQueries()
    {
        return count($this -> history);
    }

    /**
     * ыполнение запроса к базе данных с сохранением отладочной информации
     * @throws Schema\Exception
     * @return bool
     */
    public function query()
    {
        if(func_num_args() > 0) {
            call_user_func_array(array($this, 'prepare'), func_get_args());
        }
        
        $original_query = $this -> query;
        $this -> query  = Database::quote($this -> query, $this -> arguments);

        $start_time = microtime(true);
        $result = $this -> exec();
        $end_time   = microtime(true);

        $error_number = $this -> getErrno();
        $error_string = $this -> getError();

        array_push(
            $this -> history,
            array(
                'query' => $this -> query,
                'start' => $start_time,
                'end'   => $end_time,
                'total' => $end_time - $start_time,
                'errno' => $error_number,
                'error' => $error_string
            )
        );

        $this -> query = $original_query;

        if($error_number > 0 and strlen($error_string)) {
            if($this -> debug === true) {
                throw new Exception('query_error', array($error_string), $error_number);
            }
            else {
                return false;
            }
        }
        else if(is_resource($result)) {
            $this -> setResult($result);
        }
        else {
            $this -> result = null;
        }

        return true;
    }

    /**
     * возвращает все поля выбранные в процессе выполнения запроса к базе данных
     * @param string|null $field_name
     * @return array
     */
    public function values($field_name = null)
    {
        $result = array();

        if(is_resource($this -> result)) {
            $this -> seek();
        }

        while(($row = $this -> fetchArray()) !== false) {
            if($field_name !== null) {
                $row = isset($row[$field_name]) ? $row[$field_name] : null;
            }
            array_push($result, $row);
        }

        $this -> free();

        return $result;
    }

    /**
     * возвращает первое поле выбранные в процессе выполнения запроса к базе данных
     * @param null $field_name
     * @return array|null
     */
    public function value($field_name = null)
    {
        $result = $this -> fetchArray();

        if($result !== false) {
            if($field_name !== null) {
                return isset($result[$field_name]) ? $result[$field_name] : null;
            }
            return $result;
        }

        return null;
    }

    /**
     * возвращает значение первой строки и первого столбца из результата запроса
     * @return mixed
     */
    public function first()
    {
        $result = $this -> fetchNumeric();
        $this -> free();
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * освобождает память от результата запроса
     * @return void
     */
    public function free()
    {
        $this -> result = null;
    }

    /**
     * обработка запроса к базе данных
     * @param string $query
     * @param array|string $arguments
     * @return Schema
     */
    public function prepare($query, $arguments = null)
    {
        $this -> setQuery($query);

        if(func_num_args() === 2) {
            $this -> arguments($arguments);
        }
        else {
            $arguments = func_get_args();
            array_shift($arguments);
            $this -> arguments($arguments);
        }

        return $this;
    }

    /**
     * метод соединеия с базой данных
     * @abstract
     * @param string $host
     * @param string $user
     * @param string $password
     * @return resource
     */
    abstract protected function connect($host, $user, $password);

    /**
     * метод выбора базы данных
     * @abstract
     * @param string $base
     * @return bool
     */
    abstract protected function select($base);

    /**
     * выполняет запрос к базе данных
     * @abstract
     * @return resource|bool
     */
    abstract public function exec();

    /**
     * извлекает ассоциативный массив из результата запроса и возвращает его
     * @abstract
     * @return array
     */
    abstract public function fetchArray();

    /**
     * извлекает данные обработанного ряда из результата запроса и возвращает его
     * @abstract
     * @return array
     */
    abstract public function fetchNumeric();

    /**
     * возвращает объект с данными извлечёнными из результата запроса
     * @abstract
     * @return object
     */
    abstract public function fetchObject();

    /**
     * возвращает колличство строк в результате запроса
     * @abstract
     * @return int
     */
    abstract public function num();

    /**
     * возвращает последний вставленный индекс
     * @abstract
     * @return int|bool
     */
    abstract public function getId();

    /**
     * возвращает количество затронутых полей
     * @abstract
     * @return int|bool
     */
    abstract public function getAffectedRows();

    /**
     * перемещает внутренний указатель в результате запроса к начальному ряду
     * @abstract
     * @return bool
     */
    abstract public function seek();

    /**
     * устанавливает кодировку соединения
     * @abstract
     * @param $charset
     * @return bool
     */
    abstract public function setCharset($charset);

    /**
     * возвращает список полей таблицы
     * @abstract
     * @param string $table_name
     * @return array
     */
    abstract public function listFields($table_name);

    /**
     * возвращает код ошибки
     * @abstract
     * @return int
     */
    abstract public function getErrno();

    /**
     * возвращает сообщение об ошибке
     * @abstract
     * @return string
     */
    abstract public function getError();

    /**
     * метод обрамления кавычками имени полей и таблицы
     * @abstract
     * @param string $name
     * @return string
     */
    abstract public function quoteName($name);

    /**
     * метод обрамления кавычками значений
     * @abstract
     * @param string $value
     * @return string
     */
    abstract public function quoteValue($value);
}