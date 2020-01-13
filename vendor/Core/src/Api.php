<?php

namespace Core;

/**
 * Description of Api
 *
 * @author Sergey Tevs
 */

use Core\Router\Router;
use Core\Render\PhpRender;

class Api {
    
    protected $router;
    
    protected $options=[
        'access_control_allow_origin' => '*',
        'access_control_allow_methods' => [
            'OPTIONS',
            'HEAD',
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE'
        ],
        'access_control_allow_headers' => [
            'Content-Type',
            'Content-Range',
            'Content-Disposition'
        ],
        'access_control_allow_credentials'=>''
    ];
    
    protected $info=[
        "server"=>"SAPSv2 RESTful Web Service",
    ];
    
    protected $error_message=[];


    public function __construct() {
        $this->send_access_control_headers();
        $this->send_content_type_header();
        $this->router=new Router();
    }
    
    public function run(){        
        $data=$this->router->getRouter();
        $obj=new $data['controller']();
        if (!isset($data['controller']) && $data['controller']===''){
            throw new Exception("Controller is not defined.", 64);
            $this->notFound();
        }
        else if ($data['action']==='notFoundAction'){
            $this->notFound();
        }
        else {
            $result=call_user_func([$obj, $data['action']]);
            $render=new PhpRender($result);
            $render->JsonRender();
        }        
        
    }
    
    public function Forbidden(){
        header('HTTP/1.1 403 Forbidden');
        echo json_encode([
            'message'=>'Forbidden',
            'code' => '403',
        ]);
    }
    
    public function notFound(){
        header('HTTP/1.1 404 Not Found');
        echo json_encode([
            'message'=>'Not Found',
            'code' => '404',
        ]);
    }
    
    protected function header($str) {
        header($str);
    }
    
    protected function send_content_type_header() {
        $this->header('Vary: Accept');
        if (strpos($this->get_server_var('HTTP_ACCEPT'), 'Application/json') !== false) {
            $this->header('Content-type: Application/json');
        } else {
            $this->header('Content-type: text/plain');
        }
    }

    protected function send_access_control_headers() {
        $this->header('Access-Control-Allow-Origin: '.$this->options['access_control_allow_origin']);
        $this->header('Access-Control-Allow-Credentials: '
            .($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
        $this->header('Access-Control-Allow-Methods: '
            .implode(', ', $this->options['access_control_allow_methods']));
        $this->header('Access-Control-Allow-Headers: '
            .implode(', ', $this->options['access_control_allow_headers']));
    }
    
    protected function get_server_var($id) {
        return @$_SERVER[$id];
    }
        
}
