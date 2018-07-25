<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Stable.php
 */

namespace Karma\Router;

use Karma\Application;
use Karma\Http\Request;

class Stable extends Base
{
    /**
     * консруктор
     * @param \Karma\Application $application
     */
    public function __construct(Application $application)
    {
        if(($directory = $application -> getConfig() -> path('path.router.stable')) and is_dir($directory)) {
            $this -> directory = rtrim($directory, '\\/');
        }
        else {
            $this -> directory = 'stable';
        }
        parent::__construct($application);
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
        $result = parent::getObject($request, $url, $params);

        if($result !== false) {
             $url    = $request -> getUrl();

            if($url == 'default/index') {
                $request -> setUrl('');
            }
            else {
                $url = preg_replace('/^default\//', '', $url);
                $url = preg_replace('/\/index$/', '', $url);
                $request -> setUrl($url);
            }
        }

        return $result;
    }

    /**
     * поиск контроллера
     * @abstract
     * @param \Karma\Http\Request $request
     * @return \Karma\Application\Controller
     */
    protected function find(Request $request)
    {
        $result = false;
        $temp_request = array();
        $controller_request = array();

        if(strlen($request -> getUrl()) > 0) {
            foreach(explode('/', $request -> getUrl()) as $part) {
                array_push($temp_request, $part);
                $current_request = implode('/', $temp_request);
                $current_params  = substr($request -> getUrl(), strlen($current_request) + 1);

                array_push(
                    $controller_request,
                    array($current_request, $current_params),
                    array($current_request . '/index', $current_params),
                    array('default/' . $current_request, $current_params)
                );
            }

            unset($temp_request, $current_request, $current_params);

            foreach(array_reverse($controller_request) as $part) {
                if(($result = $this -> getObject($request, $part[0], $part[1])) !== false) {
                    break;
                }
            }
        }

        if($result === false) {
            $result = $this -> getObject($request, 'default/index', $request -> getUrl());
        }

        return $result;
    }
}