<?php

namespace App\Controller;

use Core\AbstractController;
use Core\Model\ViewModel;
use Core\MySQL\PDOMySQL;
use Core\Helper\Helper;
use Core\Helper\LayoutHelper;
use Core\Http\Request;
use Core\Router\Router;
use Core\Pagination\Pagination;
use Core\Helper\stdObject;

use Core\Entity\Setting;
use App\Entity\Page;
use App\Entity\Page_Type;
use App\Entity\Meta;

// use WebPConvert\WebPConvert;

class PageController extends AbstractController {
    
    protected $em;

    protected function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    protected function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    protected function pageCheck($name){
        $page=$this->getEntityManager()->findByOne(Page::class, ['name'=>$name]);
        if ($page!==false) return true;
        else return false;
    }

    protected function pageTypeCheck($name){
        $page=$this->getEntityManager()->findByOne(Page_Type::class, ['name'=>$name]);
        if ($page!==false) return true;
        else return false;
    }

    public function indexAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $router=(new Router())->getRouter();

        if (isset($router['page_type'])){
            if (!$this->pageTypeCheck($router['page_type'])){
                return $this->notFoundAction();
            }
            else return $this->post($router['page_type'], $router['page_name']);
        }
        else if (isset($router['page_name'])){
            if (!$this->pageCheck($router['page_name'])){
                if (!$this->pageTypeCheck($router['page_name'])){
                    return $this->notFoundAction();
                }
                else return $this->page_type($router['page_name']);
            }
            else return $this->post($router['page_type'], $router['page_name']);
        }

        $home_name=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'home']);
        if ($home_name!==false){
            $page=$this->getEntityManager()->findByOne(Page::class, ['name'=>$home_name->__get('value'), 'status'=>'publish', 'page_type'=>'page']);
            if ($page!==false){
                $meta=$this->getEntityManager()->find(Meta::class, ['page_id'=>$page->__get('id')]);

                $view->setLayout('layout/landing');

                $view->setHeader($helper->getHeader([
                    'title'=>$page->__get('title'),
                    'img'=>$page->__get('img'),
                    'keywords'=>$page->__get('keywords'),
                    'description'=>$page->__get('content')
                ]));

                $view->setVariables([
                    'page'=>$page
                ]);
            }
            else {
                return $this->notFoundAction();
            }
        }
        else {
            return $this->notFoundAction();
        }
        return $view;
    }

    public function postAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $request=new Request();

        $page_name=$request->getRouter()['page_name'];
        $page=$this->getEntityManager()->findByOne(Page::class, ['name'=>$page_name, 'status'=>'publish', 'page_type'=>'page']);

        if ($page!==false){
            $view->setHeader($helper->getHeader([
                'title'=>$page->__get('title'),
                'img'=>$page->__get('img'),
                'keywords'=>$page->__get('keywords'),
                'description'=>$page->__get('content')
            ]));

            $view->setLayoutVariables([
                'breadcrumb'=>[
                    'Home'=>$helper->getHost(),
                    $page->__get('title')=>''
                ],
            ]);

            $view->setLayout('layout/layout');

            $view->setVariables([
                'page'=>$page,
            ]);
        }
        else {
            return $this->notFoundAction();
        }
        return $view;
    }

    protected function page_type($page_type){
        $view=new ViewModel();
        $helper=new Helper();
        $layoutHelper=new LayoutHelper();
        $request=new Request();
        $pagination=new Pagination();

        
        $page_type=$this->getEntityManager()->findByOne(Page_Type::class, ['name'=>$page_type, 'status'=>'publish']);
        if (!$page_type){
            return $this->notFoundAction();
        }

        $options=json_decode($page_type->__get('options'));
        $template=$layoutHelper->getTemplateLayout(true)[$options->layout]['liste'];
        $view->setLayout('layout/'.$options->layout);
        $view->setTemplate($template);
        
        $post_count=$this->getEntityManager()->executeQuery("SELECT count(page.id) as post_count FROM page WHERE page_type='".$page_type->__get('name')."' AND status='publish';", stdObject::class);
        $pagination->set_posts_count($post_count->get('post_count'));
        $pages=$this->getEntityManager()->find(Page::class, ['page_type'=>$page_type->__get('name'), 'status'=>'publish'], null, ['id'=>'DESC'], $pagination->get_db_limit(true));

        // $pages=$this->getEntityManager()->find(Page::class, ['page_type'=>$page_type->__get('name'), 'status'=>'publish']);

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                $page_type->__get('title')=>''
            ],
        ]);

        $view->setHeader($helper->getHeader([
            'title'=>$page_type->__get('title'),
            'img'=>$page_type->__get('img'),
            'keywords'=>$page_type->__get('keywords'),
            'description'=>$page_type->__get('content')
        ]));

        $view->setVariables([
            'pages'=>is_object($pages)?[$pages]:$pages,
            'pagination'=>$pagination
        ]);
        return $view;
    }

    protected function post($page_type, $page_name){
        $view=new ViewModel();
        $helper=new Helper();
        $layoutHelper=new LayoutHelper();
        $request=new Request();

        if ($page_type!==null) {
            $page_type=$this->getEntityManager()->findByOne(Page_Type::class, ['name'=>$page_type, 'status'=>'publish']);
            if (!$page_type){
                return $this->notFoundAction();
            }

            $options=json_decode($page_type->__get('options'));
            $template=$layoutHelper->getTemplateLayout(true)[$options->layout]['post'];
            $view->setLayout('layout/'.$options->layout);
            $view->setTemplate($template);
        }
        else {
            $page_type='page';
            $view->setLayout('layout/layout');
            $view->setTemplate('page/post');
        }

        // var_dump(is_object($page_type)?$page_type->__get('name'):$page_type);
        
        $page=$this->getEntityManager()->findByOne(Page::class, ['page_type'=>(is_object($page_type)?$page_type->__get('name'):$page_type), 'name'=>$page_name, 'status'=>'publish']);

        if (!$page){
            return $this->notFoundAction();
        }


        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                // $page_type->__get('title')=>''
            ],
        ]);

        $view->setHeader($helper->getHeader([
            'title'=>$page->__get('title'),
            'img'=>$page->__get('img'),
            'keywords'=>$page->__get('keywords'),
            'description'=>$page->__get('content')
        ]));

        $view->setVariables([
            'page'=>$page
        ]);
        return $view;
    }

    public function contactAction(){
        $view=new ViewModel();
        $helper=new Helper();

        $view->setHeader($helper->getHeader([
            'title'=>'Kontakt',
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                'Kontakt'=>''
            ],
        ]);
        return $view;
    }

    public function notFoundAction(){
        $view=new ViewModel();
        $helper=new Helper;

        $view->setHeader($helper->getHeader([
            'title'=>'Oops! Page Not Found!!!',
            'description'=>'Entschuldigung, es ist ein Fehler aufgetreten. Die angeforderte Seite wurde nicht gefunden!'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                'Oops! Page Not Found!!!'=>''
            ],
        ]);

        $view->setTemplate('error/notFound');

        return $view;
    }

}