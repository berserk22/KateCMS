<?php

namespace App\Controller;

use Core\AbstractController;
use Core\Model\ViewModel;
use Core\MySQL\PDOMySQL;
use Core\Helper\Helper;
use Core\Http\Request;
use Core\Pagination\Pagination;
use Core\Helper\stdObject;

use App\Entity\Page;
use App\Entity\Meta;
use App\Entity\Category;

class BlogController extends AbstractController {
    
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

    public function indexAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $request=new Request();
        $pagination=new Pagination();

        if (isset($request->getQuery()['category'])){
            return $this->categoryAction();
        }
        else if (isset($request->getQuery()['tag'])){
            return $this->tagAction();
        }
        else {
            $post_count=$this->getEntityManager()->executeQuery("SELECT count(page.id) as post_count FROM page WHERE page_type='post' AND status='publish';", stdObject::class);
            $pagination->set_posts_count($post_count->get('post_count'));
            $post_liste=$this->getEntityManager()->find(Page::class, ['page_type'=>'post', 'status'=>'publish'], null, ['id'=>'DESC'], $pagination->get_db_limit(true));
            $view->setVariables([
                'posts'=>is_object($post_liste)?[$post_liste]:$post_liste,
                'pagination'=>$pagination,
            ]);

            $view->setHeader($helper->getHeader([
                'title'=>'Blog',
            ]));

            $view->setLayoutVariables([
                'breadcrumb'=>[
                    'Home'=>$helper->getHost(),
                    'Blog'=>''
                ],
            ]);
        }

        $view->setLayout('layout/blog');

        return $view;
    }

    public function postAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $request=new Request();

        $page_name=$request->getRouter()['page_name'];
        $page=$this->getEntityManager()->findByOne(Page::class, ['name'=>$page_name, 'status'=>'publish', 'page_type'=>'post']);

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
                    'Blog'=>$helper->getHost().'blog/',
                    $page->__get('title')=>''
                ],
            ]);

            $view->setLayout('layout/layout');

            $view->setVariables([
                'post'=>$page
            ]);
        }
        else {
            return $this->notFoundAction();
        }

        $view->setLayout('layout/blog');
        return $view;
    }

    public function categoryAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $request=new Request();
        $pagination=new Pagination();

        $category_name=$request->getQuery()['category'];
        $category=$this->getEntityManager()->findByOne(Category::class, ['page_type'=>'post', 'name'=>$category_name]);

        $post_count=$this->getEntityManager()->executeQuery("SELECT count(page.id) as post_count FROM page INNER JOIN meta ON page.id=meta.page_id WHERE page.page_type='post' AND meta.key='category' AND meta.value LIKE '%".$category_name."%' AND page.status='publish' ORDER BY page.id DESC;", stdObject::class);
        $pagination->set_posts_count($post_count->get('post_count'));
        $post_liste=$this->getEntityManager()->executeQuery("SELECT page.* FROM page INNER JOIN meta ON page.id=meta.page_id WHERE page.page_type='post' AND meta.key='category' AND meta.value LIKE '%".$category_name."%' AND page.status='publish' ORDER BY page.id DESC ".$pagination->get_db_limit().";", Page::class);

        $view->setHeader($helper->getHeader([
            'title'=>'Kategorie: '.$category->__get('title'),
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                'Blog'=>$helper->getHost().'blog/',
                'Kategorie: '.$category->__get('title')=>''
            ],
        ]);

        $view->setVariables([
            'posts'=>is_object($post_liste)?[$post_liste]:$post_liste,
            'pagination'=>$pagination,
        ]);
        $view->setLayout('layout/blog');

        return $view;
    }

    public function tagAction(){
        $view=new ViewModel();
        $helper=new Helper();
        $request=new Request();
        $pagination=new Pagination();
        
        $tag_name=$request->getQuery()['tag'];
        $posts_count=$this->getEntityManager()->find(Page::class, ['page_type'=>'post', 'status'=>'publish']);


        $post_count=$this->getEntityManager()->executeQuery("SELECT count(page.id) as post_count FROM page WHERE page.page_type='post' AND page.keywords LIKE '%{\"tag\":\"".$tag_name."\"}%' AND page.status='publish' ORDER BY page.id DESC;", stdObject::class);
        $pagination->set_posts_count($post_count->get('post_count'));
        $post_liste=$this->getEntityManager()->executeQuery("SELECT page.* FROM page WHERE page.page_type='post' AND page.keywords LIKE '%{\"tag\":\"".$tag_name."\"}%' AND page.status='publish' ORDER BY page.id DESC ".$pagination->get_db_limit().";", Page::class);


        $view->setHeader($helper->getHeader([
            'title'=>'Tag: '.$tag_name,
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Home'=>$helper->getHost(),
                'Blog'=>$helper->getHost().'blog/',
                'Tag: '.$tag_name=>''
            ],
        ]);

        $view->setVariables([
            'posts'=>is_object($post_liste)?[$post_liste]:$post_liste,
            'pagination'=>$pagination,
        ]);


        $view->setLayout('layout/blog');

        return $view;
    }

    public function notFoundAction(){
        $view=new ViewModel();
        $helper=new Helper;

        $view->setHeader($helper->getHeader([
            'title'=>'Oops! Page Not Found!!!',
            'description'=>'Entschuldigung, es ist ein Fehler aufgetreten. Die angeforderte Seite wurde nicht gefunden!'
        ]));
        $view->setLayout('layout/blog');
        $view->setTemplate('error/notFound');

        return $view;
    }

}