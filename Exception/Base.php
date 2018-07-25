<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Base.php
 */

namespace Karma\Exception;

use Karma\Kernel;

class Base extends \Exception
{
    /**
     * регистрирует обработчик исключений
     * @static
     * @return void
     */
    public static function register()
    {
        set_exception_handler(array(__CLASS__, 'handler'));
    }

    /**
     * обработчик исключений
     * @static
     * @param \Exception $exception
     * @return void
     */
    public static function handler(\Exception $exception)
    {
        try {
            die(self::text($exception)); // @todo
        }
        catch(\Exception $exception) {
            die(self::text($exception));
        }
    }

    /**
     * возвращает строку с информацией о исключении
     * @static
     * @param \Exception $exception
     * @return string
     */
    public static function text(\Exception $exception)
    {
        return sprintf(
            '%s: #%d %s in %s:%d',
            get_class($exception),
            $exception -> getCode(),
            strip_tags($exception -> getMessage()),
            $exception -> getFile(),
            $exception -> getLine()
        );
    }

    /**
     * конструктор
     * загружает сообщение
     * если вместо кода ошибки передан массив, передаёт значения массива в строку
     * @param string $message
     * @param int|array|null $code
     * @param \Exception|int|null $previous
     */
    public function __construct($message, $code = null, $previous = null)
    {
        $message = $this -> loadMessage($message);

        if(is_array($code)) {
            $message  = call_user_func_array('sprintf', array_merge(array($message), $code));
            $code     = $previous;
            $previous = func_num_args() === 4 ? func_get_arg(3) : null;
        }

        parent::__construct($message, (int)  $code, $previous);
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        return self::text($this);
    }

    /**
     * сохраняет имя файла в котором вызвано исключение
     * @param string $file
     * @return void
     */
    public function setFile($file)
    {
        $this -> file = $file;
    }

    /**
     * сохраняет строку в которой было вызвано исключение
     * @param int $line
     * @return void
     */
    public function setLine($line)
    {
        $this -> line = $line;
    }

    /**
     * ищет полное сообщение и возвращает его, если таковое найдено не было, то возвращает исходное
     * @param string $message
     * @return string
     */
    protected function loadMessage($message)
    {
        $reflection    = new \ReflectionObject($this);
        $language      = ucfirst(Kernel::getInstance() -> get('kernel.language', Kernel::DEFAULT_LANGUAGE));
        $message_files = array(
            dirname($reflection -> getFileName()) . DIRECTORY_SEPARATOR . 'Messages' . DIRECTORY_SEPARATOR . $language . '.ini',
            dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Messages' . DIRECTORY_SEPARATOR . $language . '.ini'
        );

        foreach($message_files as $file_name) {
            if(is_file($file_name) and ($data = @parse_ini_file($file_name, false)) !== false and isset($data[$message])) {
                $message = $data[$message];
            }
        }

        return $message;
    }
}