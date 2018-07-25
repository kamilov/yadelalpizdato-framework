<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Router.php
 */

namespace Karma;

use Karma\Application;
use Karma\Application\Controller;
use Karma\Http\Response;
use Karma\Http\Request;
use Karma\Router\Base;
use Karma\Router\Exception;
use Karma\Router\Stable;
use Karma\Router\Error;

class Router
{
    /**
     * @var \Karma\Application бъект приложения
     */
    protected $application;

    /**
     * @var \Karma\Router\Base[] список дополнительных маршрутизаторов
     */
    protected $external_routers = array();

    /**
     * @var \Karma\Router\Stable объект основного роутера
     */
    protected $stable_router;

    /**
     * @var \Karma\Router\Error объект маршрутзатора ошибок
     */
    protected $error_router;

    /**
     * конструктор
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this -> application   = $application;
        $this -> stable_router = new Stable($application);
        $this -> error_router  = new Error($application);
    }

    /**
     * добавляет дополнительный роутер в общий список
     * @param Router\Base $router
     * @return \Karma\Router
     */
    public function add(Base $router)
    {
        array_push($this -> external_routers, $router);
        return $this;
    }

    /**
     * запуск роутера
     * @param Http\Request|null $request
     * @return \Karma\Http\Response
     */
    public function execution(Request $request = null)
    {
        $controller = false;
        $result     = false;
        $status     = false;

        if($request === null) {
            $request = new Request();
        }

        foreach($this -> external_routers as $router) {
            if(($controller = $router -> get($request)) !== false) {
                break;
            }
        }

        if($controller === false) {
            $controller = $this -> stable_router -> get($request);
        }

        if($controller !== false) {
            $result = $this -> call($controller);
        }

        if($result === false) {
            $result = $this -> error(($controller ? $controller : null));
        }

        return $result;
    }

    /**
     * озвращает ответ сервера об ошибке клиента
     * @param Application\Controller|null $controller
     * @return bool|Http\Response
     */
    protected function error(Controller $controller = null)
    {
        $status = ($controller !== null and $controller -> getResponse() !== null) ? $controller -> getResponse() -> getStatus() : 404;
        $result = false;
        
        $controller = $this -> error_router -> get(new Request($status));

        if($controller !== false) {
            $result = $this -> call($controller);
        }

        if($result === false) {
            $result = new Response(Response::$messages[$status], $status);
        }

        return $result;
    }

    /**
     * обработка параметров вызов нужного метода
     * @param Application\Controller $controller
     * @return \Karma\Http\Response|bool
     */
    protected final function call(Controller $controller)
    {
        if($controller -> getAction() === null) {
            return false;
        }

        $callback = array($controller, $controller -> getRequest() -> getMethod() . ucfirst($controller -> getAction()));

        if(is_callable($callback)) {
            $url_home = '/' . ($this -> application -> getLanguage() -> isMultiLanguage() ? $this -> application -> getLanguage() -> getCurrent() . '/' : '');
            $url_self = '/' . (strlen($controller -> getRequest() -> getUrl()) > 0 ? $controller -> getRequest() -> getUrl() . '/' : '');
            $url_this = $url_home . substr($url_self, 1);

            $_REQUEST['url'] = array(
                'home' => $url_home,
                'self' => $url_self,
                'this' => $url_this
            );

            $controller -> getRequest() -> parameters -> parameters(array('url' => $_REQUEST['url']));

            if($controller -> before() !== false and call_user_func($callback) !== false and $controller -> after() !== false) {
                return $controller -> getResponse();
            }
        }

        return false;
    }
}