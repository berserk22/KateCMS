<?php

namespace Core\Router;

use Core\Http\Request;
use Core\Config\Config;
use Core\Log\Logger;

class Router {

    protected $router;

    protected $responseCode = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    public function __construct() {
        $this->router=$this->getConfig();
    }
    
    public function getConfig(){
        return Config::getConfig('router');
    }

    public function redirect($name, array $variable = null, array $parameter = null, $http_code=200){
        $router=$this->router[$name];
        $url=$router['options']['route'];
        if ($variable!==null){
            foreach ($variable as $key => $value) {
                $url=str_replace('[:'.$key.'/]', $value.'/', $url);
            }
        }
        else {
            preg_match('([^:{\[\]]*)', $url, $matches);
            $url=$matches[0];
        }
        header("HTTP/1.1 ".$http_code." ".$this->responseCode[$http_code]);
        $url= str_replace(['//','///'], '', $url);
        $query='';
        if ($parameter!==null){
            foreach ($parameter as $key => $value) {
                if ($query===''){
                    $query.='?'.$key.'='.$value;
                }
                else {
                    $query.='&'.$key.'='.$value;
                }
            }
        }
        // var_dump(substr(Config::getConfig('host'), 0, -1).$url.$query);
        //Logger::Debug(Config::getConfig('host').$url.$query);
        header('Location: '.substr(Config::getConfig('host'), 0, -1).$url.$query);
        exit();
    }
    
    public function redirectToUrl($url, $http_code=302){
        header("HTTP/1.1 ".$http_code." ".$this->responseCode[$http_code]);
        header('Location: '.$url);
    }

    public function getRouter(){
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);
        $routers=Config::getConfig('router');
        $delimiter='/';
        $router_name='home';

        if(isset($parsed_url['path'])){
            $path = $parsed_url['path'];
        } else{
            $path = '/';
        }

        $router=explode($delimiter, $path);
        unset($router[count($router)-1], $router[0]);

        if (strlen($router[1])===2){
            if (isset($routers[$router[2]])) $router_name=$router[2];
            return $this->getControllerAction($path, $routers[$router_name], true);
        }
        else if (strlen($router[1])>2){
            if (isset($routers[$router[1]])) $router_name=$router[1];
            return $this->getControllerAction($path, $routers[$router_name]);
        }
        else if (empty($router)){
            return $this->getControllerAction($path, $routers[$router_name]);
        }
    }

    protected function getControllerAction($path='/', $router=[], $lang=false){
        $delimiter='/';
        $path=urldecode($path);
        $option=$router['options'];
        $controller=$option['default']['controller'];
        $action=$option['default']['action'];

        $param_liste=[
            'controller'=>$option['default']['controller'],
            'action'=>$option['default']['action'].'Action',
        ];

        preg_match_all('/([:[a-zA-Z0-9_-]*\/])/', $option['route'], $matches);

        $count=0;
        foreach(explode($delimiter, $path) as $tmp_page){
            if ($tmp_page!==''){
                $count++;
            }
        }

        $router_keys=$matches[0];
        $pattern=$option['route'];

        if (count($router_keys)>$count){
            $unset=$router_keys[0];
        }

        foreach($router_keys as $key) {
            if (isset($unset) && $unset===$key) $pattern=str_replace($unset, '', $pattern);
            else {
                $tmp_key=str_replace(['[:', '/]'], '', $key);
                $tmp_pattern='(?P<'.$tmp_key.'>'.$option['constrain'][$tmp_key].')\/';
                $pattern=str_replace($key, $tmp_pattern, $pattern);
            }
        }
        $pattern=substr($pattern, 0, -2)."#";
        $pattern='#'.$pattern;

        preg_match($pattern, $path, $page_matches);

        if (isset($page_matches['page_name'])) $page_name=$page_matches['page_name'];

        if (!is_null($page_matches)){
            foreach($page_matches as $key=>$value){
                if (!is_int($key)) $param_liste[$key]=$value;
            }
        }
            

        if (!file_exists(ROOT_DIR.'/module/'.str_replace(['\\', 'App', 'Api', 'apiController'], ['/', 'app', 'api', 'ApiController'], $controller).'.php')){
            $controller='App\Controller\ErrorController';
            $matches['action']='notFound';
        }
        else if(is_callable([$controller, $page_name.'Action'])){
            $param_liste['action']=$page_name.'Action';
        }
        else {
            if (!is_null($page_name)) $param_liste['action']='indexAction';
            else $param_liste['action']=$action.'Action';
        }

        // var_dump($param_liste);

        return $param_liste;
    }
}