<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Kernel.php
 */

namespace Karma;

use Karma\Exception\Base as Exception;

class Kernel
{
    /**
     * разделитель пространств имён по умолчанию
     */
    const AUTOLOAD_DEFAULT_SEPARATOR = '\\';

    /**
     * разширение файлов по умолчанию
     */
    const AUTOLOAD_DEFAULT_EXTENSION = 'php';

    /**
     * имя загрузчика по умолчанию
     */
    const LOADER_NAME = 'kernel';

    /**
     * языковая версия системных сообщений по умолчанию
     */
    const DEFAULT_LANGUAGE = 'ru';

    /**
     * @var \Karma\Kernel экземпляр объекта
     */
    protected static $instance;

    /**
     * @var \Karma\Autoload[] список загрузчиков
     */
    protected $loaders = array();

    /**
     * @var array реестр ядра
     */
    protected $registry = array();

    /**
     * возвращает экземпляр объекта
     * @static
     * @return Kernel
     */
    public static function getInstance()
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * возвращает флаг определяющий что скрипт запущен через консоль
     * @return bool
     */
    public static function isCli()
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * возвращает парметры переданные через коммандную строку
     * @static
     * @param string $arguments
     * @return array
     */
    public static function getCliArguments($arguments)
    {
        if(!isset($_SERVER['argc']) or $_SERVER['argc'] < 2) {
            return array();
        }
        
        $arguments = func_get_args();
        $result    = array();

        for($i = 1; $i < $_SERVER['argc']; $i ++) {
            $option = $_SERVER['argv'][$i];
            
            if(strpos($option, '-') !== 0) {
                continue;
            }

            $option = substr($option, 1);

            if(strpos($option, '=') !== false) {
                list($option, $value) = explode('=', $option, 2);
            }
            else {
                $value = null;
            }

            if(in_array($option, $arguments)) {
                $result[$option] = $value;
            }
        }

        return $result;
    }

    /**
     * возвращает имя класса из подгруженного файла
     * @static
     * @param string $class_path
     * @return string
     */
    public static function getClassName($class_path)
    {
        if(is_file($class_path)) {
            $classes = get_declared_classes();
            require_once $class_path;
            $classes = array_reverse(array_diff(get_declared_classes(), $classes));

            if(($name = reset($classes)) !== false) {
                return $name;
            }
        }
        return false;
    }

    /**
     * проверяет массив на наличие хэш ключей
     * @static
     * @param array $array
     * @return bool
     */
    public static function arrayKeysIsInt(array $array)
    {
        foreach($array as $key => $value) {
            if(!is_int($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * конструктор
     */
    protected function __construct()
    {
        $this -> getLoader(self::LOADER_NAME)
              -> addNameSpace(__NAMESPACE__, dirname(__FILE__));

        $this -> set('kernel.start.time', microtime(true), true)
              -> set('kernel.start.memory', memory_get_usage(true), true)
              -> set('kernel.language', self::DEFAULT_LANGUAGE);
    }

    /**
     * возвращает объект загрузчика
     * @param string $name
     * @param string|null $separator
     * @param string|null $extension
     * @return Autoload
     */
    public function getLoader($name, $separator = null, $extension = null)
    {
        if(!isset($this -> loaders[$name])) {
            if($separator === null) {
                $separator = self::AUTOLOAD_DEFAULT_SEPARATOR;
            }

            if($extension === null) {
                $extension = self::AUTOLOAD_DEFAULT_EXTENSION;
            }

            $this -> loaders[$name] = new Autoload($separator, $extension);
        }

        return $this -> loaders[$name];
    }

    /**
     * сохраняет переменную в реестре ядра
     * @throws Exception\Base
     * @param string $name
     * @param mixed $value
     * @param bool $replace
     * @return Kernel
     */
    public function set($name, $value, $replace = false)
    {
        if(isset($this -> registry[$name]) and $this -> registry[$name][1] === true) {
            throw new Exception('rewriting_variable_banned', array($name));
        }
        $this -> registry[$name] = array($value, $replace);
        return $this;
    }

    /**
     * возвращает переменную из реестра ядра
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        if($this -> has($name)) {
            return $this -> registry[$name][0];
        }
        return $default;
    }

    /**
     * проверка наличия переменной в реестре
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this -> registry);
    }
}

class Autoload
{
    /**
     * @var array список путей к файлам с известными классами
     */
    protected static $known_paths = array();

    /**
     * @var string разделитель пространств имён
     */
    protected $separator;

    /**
     * @var string расширение файлов
     */
    protected $extension;

    /**
     * @var array список простанств имён
     */
    protected $name_spaces = array();

    /**
     * добавляет в список известных путей, путь к указаномму классу
     * @static
     * @param string|array $class_name
     * @param string|null $class_path
     * @return void
     */
    public static function addKnownPath($class_name, $class_path = null)
    {
        if(is_array($class_name)) {
            foreach($class_name as $class_name => $class_path) {
                self::addKnownPath($class_name, $class_path);
            }
        }
        else {
            self::$known_paths[$class_name] = $class_path;
        }
    }

    /**
     * конструктор
     * @param string $separator
     * @param string $extension
     */
    public function __construct($separator, $extension = Kernel::AUTOLOAD_DEFAULT_EXTENSION)
    {
        $this -> separator = $separator;
        $this -> extension = $extension;
        spl_autoload_register(array($this, 'load'));
    }

    /**
     * возвращает разделитель пространств имён
     * @return string
     */
    public function getSeparator()
    {
        return $this -> separator;
    }

    /**
     * возвращает расширение загружаемых файлов
     * @return string
     */
    public function getExtension()
    {
        return $this -> extension;
    }

    /**
     * добавляет в общий список пронстрантво имён
     * @param string $name
     * @param string $directory
     * @param bool $set_include_path
     * @return Autoload
     */
    public function addNameSpace($name, $directory, $set_include_path = false)
    {
        $directory = rtrim($directory, '\\/');

        if($set_include_path) {
            $paths = explode(PATH_SEPARATOR, get_include_path());

            if(array_search($directory, $paths) === false) {
                array_push($paths, $directory . DIRECTORY_SEPARATOR);
            }

            set_include_path(implode(PATH_SEPARATOR, $paths));
        }

        $this -> name_spaces[$name] = $directory;
        return $this;
    }

    /**
     * загрузка класса
     * @param string $class_name
     * @return bool|mixed
     */
    public function load($class_name)
    {
        if(isset(self::$known_paths[$class_name])) {
            return require_once self::$known_paths[$class_name];
        }

        foreach($this -> getSortSpaces() as $name => $directory) {
            if(strpos($class_name, $name) === 0) {
                foreach($this -> getClassPaths($class_name, $name, $directory) as $file_name) {
                    if(is_file($file_name)) {
                        return require_once $file_name;
                    }
                }
            }
        }
        return false;
    }

    /**
     * возвращает отсортированный список имён
     * @return array
     */
    protected function getSortSpaces()
    {
        $result = array();
        $keys   = array_keys($this -> name_spaces);
        ksort($keys);
        
        foreach($keys as $key) {
            $result[$key] = $this -> name_spaces[$key];
        }

        return $result;
    }

    /**
     * возвращает искомые пути к классу
     * @param string $class_name
     * @param string $name
     * @param string $directory
     * @return array
     */
    protected function getClassPaths($class_name, $name, $directory)
    {
        $class_path = $directory . DIRECTORY_SEPARATOR . str_replace($this -> separator, DIRECTORY_SEPARATOR, substr($class_name, strlen($name) + 1));
        return array(
            $class_path . '.' . $this -> extension,
            $class_path . DIRECTORY_SEPARATOR . basename($class_path) . '.' . $this -> extension,
            dirname($class_path) . '.' . $this -> extension
        );
    }
}

class ArrayObject implements \ArrayAccess, \Countable, \Iterator, \Serializable
{
    /**
     * @var \Karma\ArrayObject[]|array|mixed список хранимых данных
     */
    protected $data = array();

    /**
     * конструктор
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this -> data = $data;
    }

    /**
     * ковертация объкта в сроку
     * @return string
     */
    public function __toString()
    {
        return 'Array';
    }

    /**
     * клонирование объекта
     * @return void
     */
    public function __clone()
    {
        $this -> data = $this -> toArray();
    }

    /**
     * вызов объекта как функции
     * @param string $path
     * @param mixed|null $default
     * @return ArrayObject|mixed|null
     */
    public function __invoke($path, $default = null)
    {
        return $this -> path($path, $default);
    }

    /**
     * сохраненеие даных через объект
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this -> set($name, $value);
    }

    /**
     * возвращает значение данных через объект
     * @param string $name
     * @return ArrayObject|mixed|null
     */
    public function __get($name)
    {
        return $this -> get($name, null);
    }

    /**
     * проверка наличия данных через объект
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this -> has($name);
    }

    /**
     * удаление данных через объект
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        $this -> delete($name);
    }

    /**
     * массовое добавление данных
     * @param array $data
     * @return ArrayObject
     */
    public function add(array $data)
    {
        foreach($data as $name => $value) {
            $this -> set($name, $value);
        }
        return $this;
    }

    /**
     * сохранение данных
     * @param $name
     * @param $value
     * @return ArrayObject
     */
    public function set($name, $value)
    {
        $this -> data[$name] = $value;
        return $this;
    }

    /**
     * возвращает данные
     * @param string $name
     * @param mixed|null $default
     * @return ArrayObject|null
     */
    public function get($name, $default = null)
    {
        if($this -> has($name)) {
            return is_array($this -> data[$name]) ? new self($this -> data[$name]) : $this -> data[$name];
        }
        return $default;
    }

    /**
     * проверка наличия данных
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this -> data);
    }

    /**
     * удаление данных
     * @param string $name
     * @return ArrayObject
     */
    public function delete($name)
    {
        unset($this -> data[$name]);
        return $this;
    }

    /**
     * быстрый доступ к многомерному массиву
     * @param string $path
     * @param mixed|null $default
     * @return ArrayObject|mixed|null
     */
    public function path($path, $default = null)
    {
        $parts  = explode('.', $path);
        $result = $this;

        foreach($parts as $part) {
            if($result -> has($part)) {
                $result = $result -> get($part);
            }
            else {
                $result = $default;
                break;
            }
        }

        return $result;
    }

    /**
     * возвращает весь список данных в виде массива
     * @return array
     */
    public function toArray()
    {
        $result = array();

        foreach($this -> data as $name => $value) {
            if($value instanceof self) {
                $value = $value -> toArray();
            }
            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * возвращает ключи данных
     * @return array
     */
    public function keys()
    {
        return array_keys($this -> data);
    }

    /**
     * сохраненеие даных через массив
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this -> set($name, $value);
    }

    /**
     * возвращает значение данных через массив
     * @param string $name
     * @return ArrayObject|mixed|null
     */
    public function offsetGet($name)
    {
        return $this -> get($name);
    }

    /**
     * проверка наличия данных через массив
     * @param string $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return $this -> has($name);
    }

    /**
     * удаление данных через массив
     * @param string $name
     * @return bool
     */
    public function offsetUnset($name)
    {
        $this -> delete($name);
    }

    /**
     * возвращает колличество сохранённых объктов
     * @return int
     */
    public function count()
    {
        return count($this -> data);
    }

    public function current()
    {
        return current($this ->data);
    }

    public function key()
    {
        return key($this -> data);
    }

    public function next()
    {
        return next($this -> data);
    }

    public function rewind()
    {
        return reset($this -> data);
    }

    public function valid()
    {
        return $this -> current() !== false;
    }

    /**
     * сериализация объекта
     * @return string
     */
    public function serialize()
    {
        return serialize($this -> data);
    }

    /**
     * унсериализация объкта
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        $this -> add(unserialize($serialized));
    }
}