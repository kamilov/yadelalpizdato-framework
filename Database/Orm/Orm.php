<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Orm.php
 */

namespace Karma\Database;

use Karma\Autoload;
use Karma\Kernel;
use Karma\Database\Orm\Exception;
use Karma\Database\Orm\Mapper;

final class Orm
{
    /**
     * имя пространства для мапперов
     */
    const MAPPER_NAME_SPACE = 'Mapper';

    /**
     * имя пространства для моделей днных
     */
    const MODEL_NAME_SPACE = 'Model';
    
    /**
     * @var \Karma\Autoload автозагрузчик мапперов
     */
    private static $mapper_loader;

    /**
     * @var \Karma\Autoload автозагрузчик моделей данных
     */
    private static $model_loader;

    /**
     * @var string|null разделитель пространств имён
     */
    private static $space_separator;

    /**
     * @var string|null расширение системных файлов
     */
    private static $file_extension;

    /**
     * сохраняет разделитель пространсв имён для загрузчиков
     * @static
     * @param string $separator
     * @return void
     */
    public static function setSpaceSeparator($separator)
    {
        self::$space_separator = $separator;
    }

    /**
     * озвращает разделитель пространсв имён для загрузчиков
     * @static
     * @return null|string
     */
    public static function getSpaceSeparator()
    {
        if(self::$space_separator !== null) {
            return self::$space_separator;
        }
        else {
            return Kernel::getInstance() -> getLoader(Kernel::LOADER_NAME) -> getSeparator();
        }
    }

    /**
     * сохраняет расширение загружаемых файлов
     * @static
     * @param string $extension
     * @return void
     */
    public static function setFileExtension($extension)
    {
        self::$file_extension = $extension;
    }

    /**
     * возвращакет расширение загружаемых файлов
     * @static
     * @return null|string
     */
    public static function getFileExtension()
    {
        if(self::$file_extension !== null) {
            return self::$file_extension;
        }
        else {
            return Kernel::getInstance() -> getLoader(Kernel::LOADER_NAME) -> getExtension();
        }
    }

    /**
     * сохраняет директорию, в которой храняться мапперы
     * @static
     * @throws Orm\Exception
     * @param string $directory
     * @return void
     */
    public static function setMapperDirectory($directory)
    {
        if(!is_dir($directory)) {
            throw new Exception('directory_not_found', array($directory));
        }

        if(self::$mapper_loader === null) {
            self::$mapper_loader = new Autoload(self::getSpaceSeparator(), self::getFileExtension());
        }

        self::$mapper_loader -> addNameSpace(self::MAPPER_NAME_SPACE, $directory);
    }

    /**
     * сохраняет директорию, в которой модели данных
     * @static
     * @throws Orm\Exception
     * @param string $directory
     * @return void
     */
    public static function setModelDirectory($directory)
    {
        if(!is_dir($directory)) {
            throw new Exception('directory_not_found', array($directory));
        }

        if(self::$model_loader === null) {
            self::$model_loader = new Autoload(self::getSpaceSeparator(), self::getFileExtension());
        }

        self::$model_loader -> addNameSpace(self::MODEL_NAME_SPACE, $directory);
    }

    /**
     * возвращает объект маппера
     * @static
     * @throws Orm\Exception
     * @param string $name
     * @param array|null $map
     * @param array $options
     * @param array $relations
     * @return \Karma\Database\Orm\Mapper
     */
    public static function getMapper($name, array $map = null, array $options = array(), array $relations = array())
    {
        if(class_exists($name, true)) {
            return new $name($map, $options, $relations);
        }

        $class_name = sprintf(
            '%s%s%s',
            self::MAPPER_NAME_SPACE,
            self::getSpaceSeparator(),
            implode(
                self::getSpaceSeparator(),
                array_map(
                    function($name) {
                        return ucfirst($name);
                    },
                    preg_split('/[\\\._]+/', $name)
                )
            )
        );

        if(!class_exists($class_name, true)) {
            throw new Exception('mapper_not_found', array($name));
        }

        return new $class_name($map, $options, $relations);
    }

    /**
     * возвращает объкт модели данных
     * @static
     * @throws Orm\Exception
     * @param Orm\Mapper $mapper
     * @return \Karma\Database\Orm\Model
     */
    public static function getModel(Mapper $mapper)
    {
        if(class_exists($mapper -> getModel(), true)) {
            $class_name = $mapper -> getModel();
            return new $class_name($mapper);
        }

        $class_name = sprintf(
            '%s%s%s',
            self::MODEL_NAME_SPACE,
            self::getSpaceSeparator(),
            implode(
                self::getSpaceSeparator(),
                array_map(
                    function($name) {
                        return ucfirst($name);
                    },
                    preg_split('/[\\\._]+/', $mapper -> getModel())
                )
            )
        );

        if(!class_exists($class_name, true)) {
            throw new Exception('model_not_found', array($mapper -> getModel()));
        }

        return new $class_name($mapper);
    }
}