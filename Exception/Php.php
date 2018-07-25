<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Php.php
 */

namespace Karma\Exception;

class Php
{
    /**
     * регистрирует обработчик ошибок
     * @static
     * @return void
     */
    public static function register()
    {
        set_error_handler(array(__CLASS__, 'handler'));
    }

    /**
     * обработчик ошибок
     * @static
     * @throws \ErrorException
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public static function handler($code, $message, $file, $line)
    {
        throw new \ErrorException($message, $code, $code, $file, $line);
    }
}