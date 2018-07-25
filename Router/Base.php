<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Base.php
 */

namespace Karma\Router;

use Karma\Application;
use Karma\Application\Controller;
use Karma\Kernel;
use Karma\Http\Request;

abstract class Base
{
    /**
     * @var \Karma\Application объект приложения
     */
    protected $application;

    /**
     * @var string путь к директории с контроллерами
     */
    protected $directory;

    /**
     * консруктор
     * @throws Exception
     * @param \Karma\Application $application
     */
    public function __construct(Application $application)
    {
        if($this -> directory === null) {
            throw new Exception('directory_not_defined', array(get_class($this)));
        }
        else if(preg_match('/^[a-z_0-9]+$/i', $this -> directory)) {
            $this -> directory = $application -> getDirectoryPath('controllers.' . $this -> directory, true);
        }

        if(!is_dir($this -> directory)) {
            echo $this -> directory;
            throw new Exception('directory_not_found', array(get_class($this)));
        }

        $this -> application = $application;
    }

    /**
     * обрабатывает запрос и ишет контроллер
     * @param \Karma\Http\Request $request
     * @return bool|Controller
     */
    public function get(Request $request)
    {
        if(($request = $this -> check($request)) !== false) {
            return $this -> find($request);
        }
        return false;
    }

    /**
     * обработка
     * поиск в строке зпроса языковой версии если необходимо
     * @param \Karma\Http\Request $request
     * @return bool|\Karma\Http\Request
     */
    protected function check(Request $request)
    {
        if(strlen($request -> getUri()) > 0 and $this -> application -> getLanguage() -> isMultiLanguage()) {
            $parts = explode('/', $request -> getUri(), 2);

            if($this -> application -> getLanguage() -> hasLanguage($parts[1])) {
                $this -> application -> getLanguage() -> setLanguage($parts[1]);
                $request -> setUrl($parts[1]);
            }
            else {
                return false;
            }
        }
        else {
            $request -> setUrl($request -> getUri());
        }

        return $request;
    }

    /**
     * поиск и определение объекта контроллера
     * @param \Karma\Http\Request $request
     * @param string $url
     * @param string $params
     * @return bool|Controller
     */
    protected function getObject(Request $request, $url, $params)
    {
        $controller_path = $this -> directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $url) . DIRECTORY_SEPARATOR . 'controller.' . Kernel::getInstance() -> getLoader(Kernel::LOADER_NAME) -> getExtension();

        if(is_file($controller_path) and ($controller_class = Kernel::getClassName($controller_path)) !== false) {
            $controller_object = new $controller_class($this -> application, $request, $params);

            if($controller_object instanceof Controller and $controller_object -> getAction() !== null) {
                Kernel::getInstance() -> set('controllers.name', basename(dirname($controller_path)));
                Kernel::getInstance() -> set('controllers.path', $controller_path);

                $request -> setUrl($url);

                return $controller_object;
            }
        }

        return false;
    }

    /**
     * поиск контроллера
     * @abstract
     * @param \Karma\Http\Request $request
     * @return Controller
     */
    abstract protected function find(Request $request);
}