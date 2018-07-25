<?php
/**
 * Created by Kamilov Ramazan
 * Email: ramazan@kamilov.ru
 * Site:  www.kamilov.ru
 * File:  Response.php
 */

namespace Karma\Http;

use Karma\Http\Cookie;

class Response
{
    /**
     * формат возврата cookies
     */
    const COOKIE_ARRAY = 'array';

    /**
     * формат возврата cookies
     */
    const COOKIE_FLAT = 'flat';

    /**
     * @var array список сообщений об ошибках
     */
    public static $messages = array(
		100 => 'Continue',
		101 => 'Switching Protocols',

		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',

		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded'
	);

    /**
     * @var string контент отдаваемый клиенту
     */
    protected $content;

    /**
     * @var string одировка отдаваемого контента
     */
    protected $charset;

    /**
     * @var string версия протокола
     */
    protected $version;

    /**
     * @var int http статус
     */
    protected $status_code;

    /**
     * @var string ообщение для http статуса
     */
    protected $status_text;

    /**
     * @var array список заголовков
     */
    protected $headers = array();

    /**
     * @var array список кукис
     */
    protected $cookies = array();

    /**
     * конструктор
     * @param string $content
     * @param int $code
     * @param array $headers
     */
    public function __construct($content, $code = 200, array $headers = array())
    {
        $this -> setContent($content)
              -> setStatus($code)
              -> setProtocolVersion('1.0');

        foreach($headers as $name => $value) {
            $this -> setHeader($name, $value);
        }
    }

    /**
     * конвертация объекта в строку
     * @return string
     */
    public function __toString()
    {
        return $this -> prepare()
                     -> sendHeaders()
                     -> getContent();
    }

    /**
     * подгатовка заголовков
     * @return Response
     */
    public function prepare()
    {
        if($this -> isInformational() and in_array($this -> status_code, array(204, 304))) {
            $this -> setContent('');
        }

        $charset = $this -> charset ?: 'utf-8';

        if($this -> hasHeader('content-type') === false) {
            $this -> setHeader('content-type', 'text/html; charset=' . $charset);
        }
        else if(substr($this -> getHeader('content-type'), 0, 5) === 'text/' and strpos($this -> getHeader('content-type'), 'charset') === false) {
            $this -> setHeader('content-type', ($this -> getHeader('content-type') . '; charset=' . $charset));
        }

        return $this;
    }

    /**
     * отправка заголовков клиенту
     * @return Response
     */
    public function sendHeaders()
    {
        if(headers_sent() === false) {
            $this -> prepare();

            header(sprintf('HTTP/%s %s %s', $this -> version, $this -> status_code, $this -> status_text));

            foreach($this -> headers as $name => $values) {
                foreach($values as $value) {
                    header(
                        preg_replace_callback(
                            '/\-(.)/',
                            function($matches){
                                return '-' . strtoupper($matches[1]);
                            },
                            ucfirst($name)
                        ) . ': ' . $value
                    );
                }
            }

            foreach($this -> getCookies() as $cookie) {
                setcookie($cookie -> getName(), $cookie -> getValue(), (time() + $cookie -> getExpire()), $cookie -> getPath(), $cookie -> getDomain(), $cookie -> isSecure(), $cookie -> isHttpOnly());
            }
        }

        return $this;
    }

    /**
     * отправка контента клиенту
     * @return void
     */
    public function sendContent()
    {
        echo $this -> getContent();
    }

    /**
     * отправка заголовоков и контента клиенту
     * @return void
     */
    public function send()
    {
        $this -> sendHeaders()
              -> sendContent();
    }

    /**
     * сохранение контента
     * @throws Exception
     * @param string $content
     * @return Response
     */
    public function setContent($content)
    {
        if($content !== null and !is_string($content) and !is_numeric($content) and (is_object($content) and !is_callable($content, '__toString'))) {
            throw new Exception('invalid_content_type', array(gettype($content)));
        }
        $this -> content = (string) $content;
        return $this;
    }

    /**
     * возвращает контент
     * @return string
     */
    public function getContent()
    {
        return $this -> content;
    }

    /**
     * сохраняет кодировку
     * @param string $charset
     * @return Response
     */
    public function setCharset($charset)
    {
        $this -> charset = $charset;
        return $this;
    }

    /**
     * возвращает кодировку
     * @return string
     */
    public function getCharset()
    {
        return $this -> charset;
    }

    /**
     * сохраняет http статус
     * @throws Exception
     * @param integer $code
     * @param string|null $text
     * @return Response
     */
    public function setStatus($code, $text = null)
    {
        $this -> status_code = (int) $code;

        if($this -> isValid() === false) {
            throw new Exception('status_not_valid', array($this -> status_code));
        }

        $this -> status_text = $text === false ? '' : ($text === null ? self::$messages[$this -> status_code] : $text);

        return $this;
    }

    /**
     * возвращает код http статуса
     * @return int
     */
    public function getStatus()
    {
        return $this -> status_code;
    }

    /**
     * схраняет версию http протокола
     * @param string $version
     * @return Response
     */
    public function setProtocolVersion($version)
    {
        $this -> version = $version;
        return $this;
    }

    /**
     * возвращает версию http протокола
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this -> version;
    }

    /**
     * сохранение заголовка
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return Response
     */
    public function setHeader($name, $value, $replace = true)
    {
        $name  = strtr(strtolower($name), '_', '-');
        $value = (array) $value;

        if($replace or $this -> hasHeader($name) === false) {
            $this -> headers[$name] = $value;
        }
        else {
            $this -> headers[$name] = array_merge($this -> headers[$name], $value);
        }

        return $this;
    }

    /**
     * возвращает заголовок
     * @param string $name
     * @param null|mixed $default
     * @param bool $first
     * @return array|null
     */
    public function getHeader($name, $default = null, $first = true)
    {
        if($this -> hasHeader($name) === false) {
            if($default === null) {
                return $first ? null : array();
            }
            return $first ? $default : array($default);
        }

        $name = strtr(strtolower($name), '_', '-');

        if($first) {
            return count($this -> headers[$name]) > 0 ? $this -> headers[$name][0] : $default;
        }

        return $this -> headers[$name];
    }

    /**
     * проверка наличия заголовка
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return array_key_exists(strtr(strtolower($name), '_', '-'), $this -> headers);
    }

    /**
     * удаление заголовка
     * @param string $name
     * @return void
     */
    public function deleteHeader($name)
    {
        unset($this -> headers[strtr(strtolower($name), '_', '-')]);
    }

    /**
     * сохранение cookie
     * @param Cookie $cookie
     * @return Response
     */
    public function setCookie(Cookie $cookie)
    {
        $this -> cookies[$cookie -> getDomain()][$cookie -> getPath()][$cookie -> getName()] = $cookie;
        return $this;
    }

    /**
     * возвращает список кукис в указаном формате
     * @throws Exception
     * @param string $format
     * @return array|Cookie[]
     */
    public function getCookies($format = self::COOKIE_FLAT)
    {
        if(!in_array($format, array(self::COOKIE_ARRAY, self::COOKIE_FLAT))) {
            throw new Exception('cookie_invalid_format', $format);
        }

        if($format === self::COOKIE_ARRAY) {
            return $this -> cookies;
        }

        $result = array();

        foreach($this -> cookies as $path) {
            foreach($path as $cookies) {
                foreach($cookies as $cookie) {
                    array_push($result, $cookie);
                }
            }
        }

        return $result;
    }

    /**
     * удаление куки
     * @param string $name
     * @param string|null $path
     * @param string|null $domain
     * @return Response
     */
    public function deleteCookie($name, $path = null, $domain = null)
    {
        unset($this -> cookies[$domain][$path][$name]);

        if(empty($this -> cookies[$domain][$path])) {
            unset($this -> cookies[$domain][$path]);

            if(empty($this -> cookies[$domain])) {
                unset($this -> cookies[$domain]);
            }
        }

        return $this;
    }

    /**
     * проверка валидности кода http статуса
     * @return bool
     */
    public function isValid()
    {
        return $this -> status_code >= 100 and $this -> status_code < 600;
    }

    /**
     * возвращает флаг определяющий отпавку информационных заголовков
     * @return bool
     */
    public function isInformational()
    {
        return $this -> status_code >= 100 and $this -> status_code < 200;
    }

    /**
     * возвращает флаг определяющий, что клиент получает зарашиваемый контент
     * @return bool
     */
    public function isSuccessful()
    {
        return $this -> status_code >= 200 and $this -> status_code < 300;
    }

    /**
     * возвращает флаг, если http татус установлен на переадресацию
     * @return bool
     */
    public function isRedirect()
    {
        return $this -> status_code >= 300 and $this -> status_code < 400;
    }

    /**
     * возвращет флаг, если http статус определён как ошибка клиента
     * @return bool
     */
    public function isClientError()
    {
        return $this -> status_code >= 400 and $this -> status_code < 500;
    }

    /**
     * возвращет флаг, если http статус определён как ошибка сервера
     * @return bool
     */
    public function isServerError()
    {
        return $this -> status_code >= 500 and $this -> status_code < 600;
    }

    /**
     * возвращает флаг, если http статус возвращает клиенту запашиваемый контент
     * @return bool
     */
    public function isOk()
    {
        return $this -> status_code === 200;
    }

    /**
     * возвращает флаг, если http статус определяет запрет доступа
     * @return bool
     */
    public function isForbidden()
    {
        return $this -> status_code === 403;
    }

    /**
     * возвращает флаг, если http статус определяет недоступность документа
     * @return bool
     */
    public function isNotFound()
    {
        return $this -> status_code === 404;
    }

    /**
     * возвращает флаг, если http статус определяет пустой ответ
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this -> status_code, array(201, 204, 304));
    }
}