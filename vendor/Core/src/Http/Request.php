<?php
namespace Core\Http;

use Core\Config\Config;
use Core\Router\Router;

class Request {

    protected $httpRequestMethod;

    protected $post=[];

    protected $query=[];

    protected $put;

    public function __construct() {
        // $this->setHttpRequestMethod();
        $this->setInputData();
    }

    // protected function setHttpRequestMethod() {
    //     $this->httpRequestMethod = $this->validateHttpRequestMethod($_SERVER['REQUEST_METHOD']);
    // }

    // protected function validateHttpRequestMethod($input) {
    //     if(empty($input)) {
    //         throw new InvalidArgumentException('I need valid value');
    //     }
    //     switch ($input) {
    //         case 'GET':
    //         case 'POST':
    //         case 'PUT':
    //         case 'DELETE':
    //         case 'HEAD':
    //             return $input;
    //             break;
    //         default:
    //             throw new InvalidArgumentException('Unexpected value.');
    //             break;
    //     }
    // }

    protected function setInputData() {
        $this->setQuery();
        $this->setPost();
        $this->setPut();
        $this->setUri();
    }

    public function setPost(){
        $this->post=filter_input_array(INPUT_POST);
    }

    public function setQuery(){
        $this->query=filter_input_array(INPUT_GET);
    }

    public function setPut(){
        $temp_arr = [];
        if ($this->httpRequestMethod==='PUT'){
            $data = file_get_contents('php://input');
            $exploded = explode('&', $data);
            foreach($exploded as $pair) {
                $item = explode('=', $pair);
                if(count($item) == 2) {
                    $temp_arr[urldecode($item[0])] = urldecode($item[1]);
                }
            }
        }
        $this->put=$temp_arr;
    }

    public function getPost(){
        if (is_null($this->post)) return [];
        return $this->post;
    }

    public function getQuery(){
        if (is_null($this->query)) return [];
        return $this->query;
    }

    public function getPut(){
        if (is_null($this->put)) return [];
        return $this->put;
    }

    public function isPost(){
        return count($this->getPost())>0?true:false;
    }

    public function isQuery(){
        return count($this->getQuery())>0?true:false;
    }

    public function isPut(){
        return count($this->getPut())>0?true:false;
    }

    public function setUri(){
        if (!empty(filter_input(INPUT_SERVER, 'REQUEST_URI'))) {
            $this->uri = trim(filter_input(INPUT_SERVER, 'REQUEST_URI'), '/');
        }
    }
    
    public function getUri(){
        return $this->uri;
    }

    public function getRouter(){
        return (new Router())->getRouter();
    }

}