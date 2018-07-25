<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Cache.php
 */

namespace Karma;

interface Cache
{
    /**
     * время хранения данных по умолчанию
     */
    const EXPIRE = 60;

    /**
     * сохраняет данные если они ещё не существуют
     * @abstract
     * @param string $name
     * @param mixed $value
     * @param int|null $expire
     * @param array $params
     */
    public function set($name, $value, $expire = null);

    /**
     * возвращает кэш данные
     * @abstract
     * @param $name
     * @return mixed
     */
    public function get($name);

    /**
     * удаляет даныне
     * @abstract
     * @param $name
     * @return bool
     */
    public function delete($name);
}