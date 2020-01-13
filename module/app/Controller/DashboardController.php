<?php

namespace App\Controller;

use Core\AbstractController;
use Core\Model\ViewModel;
use Core\MySQL\PDOMySQL;
use Core\Helper\Helper;
use Core\Http\Request;
use Core\Pagination\Pagination;
use Core\Helper\stdObject;
use Core\Auth\Authentication as Auth;
use Core\Router\Router;
use Core\Http\Session;
use Core\Form\Filter\FormFilter;
use Core\Log\Logger;
use Core\Config\Config;
use Core\Config\SystemConfig;
use Core\File\Image;

use Core\Entity\Setting;
use App\Entity\Users;
use App\Entity\User_Role;
use App\Entity\Page;
use App\Entity\Page_Type;
use App\Entity\Meta;
use App\Entity\Category;
use App\Entity\Team;
use App\Entity\Partner;
use App\Entity\Comments;

class DashboardController extends AbstractController {

    protected $em;

    protected $setting = [
        'general' => [
            'title',
            'description',
            'admin_email',
            'users_can_register',
            'default_role',
            'date_format',
            'time_format',
            'start_of_week'
        ],
        'email' => [
            'transport',
            'host',
            'port',
            'user',
            'password',
            'mail_from',
            'ssl'
        ],
        'medien' => [
            'thumbnail_size_w',
            'thumbnail_size_h',
            'medium_size_w',
            'medium_size_h',
            'large_size_w',
            'large_size_h',
            'thumbnail_crop'
        ],
        'discussion' => [
            'default_comment_status',
            'require_name_email',
            'comment_registration',
            'comment_order',
            'comments_notify',
            'moderation_notify',
            'comment_moderation',
            'comment_whitelist'
        ],
        'reading' => [
            'posts_per_page',
            'posts_per_rss',
            'rss_use_excerpt',
        ],
        'social' => [
            'fb_login',
            'fb_page_id',
            'fb_access_token',
            'fb_page_access_token',
            'fb_app_id',
            'fb_app_secret',
            'inst_page_id',
            'inst_login',
            'inst_access_token',
            'inst_app_id',
            'long_live_token',
        ]
    ];

    protected function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    protected function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    protected function checkAdmin() {
        $auth = new Auth();
        if (!$auth->getAdminStatus()) {
            return (new Router())->redirect('dashboard', ['page_name' => 'login']);
        }
    }

    public function indexAction(){
        $view=new ViewModel();
        $helper = new Helper();
        $this->checkAdmin();

        $view->setHeader($helper->getHeader([
            'title' => 'Dashboard'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Dashboard'=>''
            ],
        ]);

        return $view;
    }

    public function loginAction(){
        $view=new ViewModel();
        $request = new Request();
        $auth = new Auth();
        $router = new Router();
        $helper = new Helper();
        $formFilter = new FormFilter();
        $view->setLayout('layout/login');

        $view->setHeader($helper->getHeader([
            'title' => 'Anmelden'
        ]));

        $view->setLayoutVariables([
            'title'=>'Anmelden'
        ]);


        if (!$auth->getAdminStatus()) {
            if ($request->isPost()) {
                $formData = $request->getPost();
                foreach ($formData as $name => $value) {
                    if ($formFilter->notEmpty($name, $value)) {
                        switch ($name) {
                            case 'email':
                                $formFilter->isEmail($name, $value);
                                break;
                            case 'password';
                                $formFilter->lenght($name, $value, 8, 35);
                                break;
                        }
                    }
                }
                $error = $formFilter->getMessages();
                if (count($error) !== 0) {
                    $view->setVariables([
                        'login_error' => $error,
                        'email' => $formData['email']
                    ]);
                    return $view;
                } else {
                    $user = $this->getEntityManager()->findByOne(Users::class, ['email' => $formData['email']]);
                    if (!$user) {
                        $view->setVariables([
                            'login_error' => 'Die Login oder das eingegebene Passwort ist ungültig.',
                            'email' => $formData['email']
                        ]);
                        return $view;
                    } else {
                        $user_role = $this->getEntityManager()->findByOne(User_Role::class, ['user_id' => $user->__get('id')]);
                        if (!$user_role) {
                            $view->setVariables([
                                'login_error' => 'Die Benetzer hat kein Zugriff Rechte.',
                                'email' => $formData['email']
                            ]);
                            return $view;
                        } else {
                            $role = $this->getEntityManager()->findByOne(User_Role::class, ['id' => $user_role->__get('role_id')]);
                            $auth->setHash($formData['password']);
                            $formData['password'] = $auth->getHash();

                            Logger::Debug('PW: '.$auth->getHash());

                            if ($user->__get('password') === $formData['password']) {
                                $auth->setAdminAuth($user->__get('id'));
                                $router->redirect('dashboard');
                            } else {
                                $view->setVariables([
                                    'login_error' => 'Die Login oder das eingegebene Passwort ist ungültig.',
                                    'email' => $formData['email']
                                ]);
                                return $view;
                            }
                        }
                    }
                }
            }
            else if ($request->isQuery() && isset($request->getQuery()['facebook'])){
                $fb = new Facebook([
                    'app_id' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_id'])->__get('value'),
                    'app_secret' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_secret'])->__get('value'),
                ]);
                $helper = $fb->getRedirectLoginHelper();
                $host=\Vendor\Config\Config::getConfig('host');
                $http_protocol=\Vendor\Config\Config::getConfig('http_protocol');

                $loginUrl = $helper->getLoginUrl($http_protocol.'://'.$host.'dashboard/login/', $this->scope);
                $router->redirectToUrl($loginUrl);
            }
            else if ($request->isQuery() && isset($request->getQuery()['code'])){
                $fb = new Facebook([
                    'app_id' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_id'])->__get('value'),
                    'app_secret' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_secret'])->__get('value'),
                ]);
                $helper = $fb->getRedirectLoginHelper();
                try {
                    $accessToken = $helper->getAccessToken();
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                    Logger::Error('Graph returned an error: '.$e->getMessage());
                    exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                    Logger::Error('Facebook SDK returned an error: '.$e->getMessage());
                    exit;
                }
                  
                if (!isset($accessToken)) {
                    if ($helper->getError()) {
                        Logger::Error("Error: ".$helper->getError());
                        Logger::Error("Error Code: ".$helper->getErrorCode());
                        Logger::Error("Error Reason: ".$helper->getErrorReason());
                        Logger::Error("Error Description: ".$helper->getErrorDescription());
                    } else {
                        Logger::Error('Bad request');
                    }
                }

                try {
                    // Returns a `Facebook\FacebookResponse` object
                    $response = $fb->get('/me?fields=id,name,first_name,last_name,email', $accessToken->getValue());
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                    echo 'Graph returned an error: ' . $e->getMessage();
                    exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                    echo 'Facebook SDK returned an error: ' . $e->getMessage();
                    exit;
                }
                  
                $fb_user = $response->getGraphUser();

                $user = $this->getEntityManager()->findByOne(Users::class, ['email' => $fb_user['email']]);
                if (!$user) {
                    $this->getEntityManager()->insert(Users::class, [
                        'firstname'=>$fb_user['first_name'],
                        'lastname'=>$fb_user['last_name'],
                        'sex'=>0,
                        'email'=>$fb_user['email'],
                        'ban'=>0,
                        'created'=>time(),
                        'updated'=>time()
                    ]);

                    $user = $this->getEntityManager()->findByOne(Users::class, ['email' => $fb_user['email']]);

                    $this->getEntityManager()->insert(User_Role::class, [
                        'user_id'=>$user->__get('id'),
                        'role_id'=>1
                    ]);

                    $user_role = $this->getEntityManager()->findByOne(User_Role::class, ['user_id' => $user->__get('id')]);
                    if (!$user_role) {
                        $view->setVariables([
                            'login_error' => 'Die Benetzer hat kein Zugriff Rechte.',
                        ]);
                        return $view;
                    } else {
                        $auth->setAdminAuth($user->__get('id'));
                        /***************************************************************************** */
                        $this->getEntityManager()->update(Setting::class, ['value' => $accessToken->getValue()], ['key' => 'fb_access_token']);
                        //$oAuth2Client = $fb->getOAuth2Client();
                        //$tokenMetadata = $oAuth2Client->debugToken($accessToken);
                        //$this->getEntityManager()->update(Setting::class, ['value' => $tokenMetadata->getExpiresAt()->getTimestamp()], ['key' => 'fb_access_token_expires_at']);
                        /***************************************************************************** */

                        $router->redirect('dashboard');
                    }
                //$view->setVariables([
                    //    'login_error' => 'Die Login oder das eingegebene Passwort ist ungültig.',
                    //]);
                    return $view;
                } else {
                    $user_role = $this->getEntityManager()->findByOne(User_Role::class, ['user_id' => $user->__get('id')]);
                    if (!$user_role) {
                        $view->setVariables([
                            'login_error' => 'Die Benetzer hat kein Zugriff Rechte.',
                        ]);
                        return $view;
                    } else {
                        $auth->setAdminAuth($user->__get('id'));
                        /***************************************************************************** */
                        $this->getEntityManager()->update(Setting::class, ['value' => $accessToken->getValue()], ['key' => 'fb_access_token']);
                        //$oAuth2Client = $fb->getOAuth2Client();
                        //$tokenMetadata = $oAuth2Client->debugToken($accessToken);
                        //$this->getEntityManager()->update(Setting::class, ['value' => $tokenMetadata->getExpiresAt()->getTimestamp()], ['key' => 'fb_access_token_expires_at']);
                        /***************************************************************************** */

                        $router->redirect('dashboard');
                    }
                }
            }
        } else {
            $router->redirect('dashboard');
        }
        return $view;
    }
    
    public function logoutAction() {
        $auth = new Auth();
        $router = new Router();
        if ($auth->getAdminStatus()) {
            $auth->removeAdminAuth();

            $this->getEntityManager()->update(Setting::class,['value'=>''], ['key' => 'fb_access_token']);
            $this->getEntityManager()->update(Setting::class,['value'=>''], ['key' => 'fb_access_token_expires_at']);

            $router->redirect('dashboard');
        } else {
            $router->redirect('dashboard');
        }
    }

    public function resetAction(){
        $view=new ViewModel();
        $request=new Request();
        $auth = new Auth();
        $router = new Router();
        $helper = new Helper();
        $view->setLayout('layout/login');

        $view->setHeader($helper->getHeader([
            'title' => 'Passwort zurücksetzen'
        ]));

        $view->setLayoutVariables([
            'title'=>'Passwort zurücksetzen'
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
        $view->setTemplate('error/notFound');
        return $view;
    }

    /************************************************************** */

    public function pagesAction() {
        $this->checkAdmin();
        $view = new ViewModel();
        $helper = new Helper();
        $request = new Request();

        $view->setHeader($helper->getHeader([
            'title' => 'Seiten'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seiten'=>''
            ],
        ]);
        $page_type="";

        if ($request->isQuery()) {
            $param = $request->getQuery();
            if (isset($param['type']) && $param['type'] !== '') {
                switch ($param['type']) {
                    case 'add':
                        return $this->page_add();
                    case 'edit':
                        return $this->page_edit();
                    case 'delete':
                        return $this->page_delete();
                    default:
                        break;
                }
            } 
            if (isset($param['post_type'])) $where['status']=$param['post_type'];
            if (isset($param['page_type']) && !empty($param['page_type'])) {
                $where['page_type']=$param['page_type']; 
                $page_type=$param['page_type'];
            }
            $posts = $this->getEntityManager()->find(Page::class, $where);
        } else {
            $posts = $this->getEntityManager()->find(Page::class);
        }

        $publish = $this->getEntityManager()->callFunction('getCountPage', [$page_type, 'publish']);
        $draft = $this->getEntityManager()->callFunction('getCountPage', [$page_type, 'draft']);
        $post_count = $this->getEntityManager()->callFunction('getCountPage', [$page_type, '']);
        $view->setVariables([
            'posts' => $posts,
            'publish' => $helper->get_array_value($publish->getArrayCopy()),
            'draft' => $helper->get_array_value($draft->getArrayCopy()),
            'post_count' => $helper->get_array_value($post_count->getArrayCopy()),
            'query' => isset($param) ? $param : null
        ]);
        return $view;
    }

    protected function page_add() {
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Seiten › Erstellen'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seite erstellen'=>''
            ],
        ]);
        $view->setTemplate('dashboard/pages/add');
        if ($request->isPost()) {
            $formData = $request->getPost();
            $author = (new Auth)->getAdminAuth();
            $this->getEntityManager()->insert(Page::class, [
                'title' => $formData['title'],
                'name' => $formData['name'],
                'guid' => $formData['guid'],
                'content' => $formData['content'],
                'keywords'=> $formData['keywords'],
                'status' => $formData['status'],
                'options'=>'[]',
                'page_type' => $formData['page_type'],
                'created' => time(),
                'updated' => time(),
            ]);
            $page = $this->getEntityManager()->findByOne(Page::class, ['name' => $formData['name'], 'page_type' => $formData['page_type']]);
            $this->getEntityManager()->insert(Meta::class, ['value' => $formData['layout'], 'page_id' => $page->__get('id'), 'key' => 'layout']);

            if ((isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') || $formData['file_name_img'] === '') {
                $this->getEntityManager()->update(Page::class, ['img' => '', 'thumb' => ''], ['id' => $page->__get('id')]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {

                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];

                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);

                    $host=Config::getConfig('host');

                    //$host = \Vendor\Config\Config::getConfig('host');
                    //$http_protocol = \Vendor\Config\Config::getConfig('http_protocol');
                    $this->getEntityManager()->update(Page::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $page->__get('id')]);
                    /*
                    $fb_photo_link=$http_protocol."://".$host."source/images/".$tmp_name;

                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                    */
                } else {
                    Logger::Error('Image no exist');
                }
            }

            // if ($formData['fb_posten']==='on'){
            //     try {
            //         $this->FBPost($formData, isset($fb_photo_link)?$fb_photo_link:null, $page->__get('id'));
            //     }
            //     catch(Exception $e){
            //         Logger::Error($e->getMessage());
            //     }
            // }

            (new Router())->redirect('dashboard', ['page_name' => 'pages']);
        }
        $view->setVariables([
            'request' => $request->getPost()
        ]);
        return $view;
    }

    protected function page_edit() {
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Seiten › Bearbeiten'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seite bearbeiten'=>''
            ],
        ]);
        $view->setTemplate('dashboard/pages/edit');
        $param = $request->getQuery();
        if ($request->isPost()) {
            $formData = $request->getPost();

            $this->getEntityManager()->update(Page::class, [
                'title' => $formData['title'],
                'name' => $formData['name'],
                'guid' => $formData['guid'],
                'content' => $formData['content'],
                'status' => $formData['status'],
                'keywords' => $formData['keywords'],
                'page_type' => $formData['page_type'],
                'options'=>'[]',
                'updated' => time(),
            ], [
                'id' => $param['post_id']
            ]);
            //$this->getEntityManager()->update(Meta::class, ['value' => $formData['keywords']], ['page_id' => $param['post_id'], 'key' => 'keywords']);
            // $this->getEntityManager()->update(Meta::class, ['value' => $formData['layout']], ['page_id' => $param['post_id'], 'key' => 'layout']);

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Page::class, ['img' => '', 'thumb' => ''], ['id' => $param['post_id']]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);
                    $host = Config::getConfig('host');
                    $this->getEntityManager()->update(Page::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $param['post_id']]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                } else {
                    Logger::Error('Image no exist');
                }
            }
        }
        $post = $this->getEntityManager()->findByOne(Page::class, ['id' => $param['post_id']]);
        $info = $this->getEntityManager()->find(Meta::class, ['page_id' => $post->__get('id')]);
        $view->setVariables([
            'post' => $post,
            'info' => $info
        ]);
        return $view;
    }

    protected function page_delete() {
        $router = new Router();
        $request = new Request();
        $param = $request->getQuery();
        $host = Config::getConfig('host');
        $page = $this->getEntityManager()->findByOne(Page::class, ['id' => $param['post_id']]);

        if ($page->__get('img') !== null || $page->__get('img') !== '') {
            $header_img = str_replace($host, '', $page->__get('img'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $header_img);
        }

        if ($page->__get('thumb') !== null || $page->__get('thumb') !== '') {
            $thumb_img = str_replace($host, '', $page->__get('thumb'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $thumb_img);
        }

        $this->getEntityManager()->remove(Meta::class, ['page_id' => $param['post_id']]);
        //$this->getEntityManager()->remove('Vendor\Entity\Comments', ['post_id' => $param['post_id']]);
        $this->getEntityManager()->remove(Page::class, ['id' => $param['post_id']]);
        $router->redirect('dashboard', ['page_name' => 'pages']);
    }

    /*************************************************************** */

    public function page_typeAction(){
        $this->checkAdmin();
        $view = new ViewModel();
        $helper = new Helper();
        $request = new Request();

        $view->setHeader($helper->getHeader([
            'title' => 'Seitentyp'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seitentyp'=>''
            ],
        ]);

        if ($request->isQuery()) {
            $param = $request->getQuery();
            if (isset($param['type']) && $param['type'] !== '') {
                switch ($param['type']) {
                    case 'add':
                        return $this->page_type_add();
                    case 'edit':
                        return $this->page_type_edit();
                    case 'delete':
                        return $this->page_type_delete();
                    default:
                        break;
                }
            } 
        } 

        $pages_type = $this->getEntityManager()->find(Page_Type::class);

        $view->setVariables([
            'pages_type'=>$pages_type
        ]);

        return $view;
    }

    protected function page_type_add(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Seitentyp › Erstellen'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seitentyp erstellen'=>''
            ],
        ]);
        $view->setTemplate('dashboard/page_type/add');

        if ($request->isPost()) {
            $formData = $request->getPost();
            $author = (new Auth)->getAdminAuth();
            $name=str_replace(['ü', 'ö', 'ä', 'ß', '&'], ['ue', 'oe', 'ae', 'ss', ''], strtolower($formData['title']));
            $this->getEntityManager()->insert(Page_Type::class, [
                'title' => $formData['title'],
                'name' => $name,
                'content' => $formData['content'],
                'keywords'=> $formData['keywords'],
                'status' => $formData['status'],
                'options'=>json_encode([
                    'layout'=>$formData['layout']
                ]),
                'created' => time(),
                'updated' => time(),
            ]);
            $page = $this->getEntityManager()->findByOne(Page_Type::class, ['name' => $name]);

            if ((isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') || $formData['file_name_img'] === '') {
                $this->getEntityManager()->update(Page_Type::class, ['img' => '', 'thumb' => ''], ['id' => $page->__get('id')]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {

                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];

                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);

                    $host=Config::getConfig('host');

                    //$host = \Vendor\Config\Config::getConfig('host');
                    //$http_protocol = \Vendor\Config\Config::getConfig('http_protocol');
                    $this->getEntityManager()->update(Page::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $page->__get('id')]);
                    /*
                    $fb_photo_link=$http_protocol."://".$host."source/images/".$tmp_name;

                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                    */
                } else {
                    Logger::Error('Image no exist');
                }
            }

            (new Router())->redirect('dashboard', ['page_name' => 'page_type']);
        }
        $view->setVariables([
            'request' => $request->getPost()
        ]);

        return $view;
    }

    protected function page_type_edit(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Seitentyp › Bearbeiten'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Seitentyp bearbeiten'=>''
            ],
        ]);
        $view->setTemplate('dashboard/page_type/edit');

        $param = $request->getQuery();
        if ($request->isPost()) {
            $formData = $request->getPost();
            $name=str_replace(['ü', 'ö', 'ä', 'ß', '&'], ['ue', 'oe', 'ae', 'ss', ''], strtolower($formData['title']));
            $this->getEntityManager()->update(Page_Type::class, [
                'title' => $formData['title'],
                'name' => $formData['name'],
                'content' => $formData['content'],
                'status' => $formData['status'],
                'keywords' => $formData['keywords'],
                'options'=>json_encode([
                    'layout'=>$formData['layout']
                ]),
                'updated' => time(),
            ], [
                'id' => $param['post_id']
            ]);
            //$this->getEntityManager()->update(Meta::class, ['value' => $formData['keywords']], ['page_id' => $param['post_id'], 'key' => 'keywords']);
            // $this->getEntityManager()->update(Meta::class, ['value' => $formData['layout']], ['page_id' => $param['post_id'], 'key' => 'layout']);

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Page_Type::class, ['img' => '', 'thumb' => ''], ['id' => $param['post_id']]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);
                    $host = Config::getConfig('host');
                    $this->getEntityManager()->update(Page_Type::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $param['post_id']]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                } else {
                    Logger::Error('Image no exist');
                }
            }
        }
        $post = $this->getEntityManager()->findByOne(Page_Type::class, ['id' => $param['post_id']]);
        $view->setVariables([
            'post' => $post,
        ]);

        return $view;
    }

    protected function page_type_delete(){
        $router = new Router();
        $request = new Request();
        $param = $request->getQuery();
        $host = Config::getConfig('host');
        $page = $this->getEntityManager()->findByOne(Page_Type::class, ['id' => $param['post_id']]);

        if ($page->__get('img') !== null || $page->__get('img') !== '') {
            $header_img = str_replace($host, '', $page->__get('img'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $header_img);
        }

        if ($page->__get('thumb') !== null || $page->__get('thumb') !== '') {
            $thumb_img = str_replace($host, '', $page->__get('thumb'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $thumb_img);
        }

        $this->getEntityManager()->remove(Page_Type::class, ['id' => $param['post_id']]);
        $router->redirect('dashboard', ['page_name' => 'page_type']);
    }

    /*************************************************************** */

    

    /*************************************************************** */

    public function postAction(){
        $this->checkAdmin();
        $view = new ViewModel();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Beiträge › Alle'
        ]));
        $helper = new Helper();
        $request = new Request();
        if ($request->isQuery()) {
            $param = $request->getQuery();
            if (isset($param['type']) && $param['type'] !== '') {
                switch ($param['type']) {
                    case 'add':
                        return $this->post_add();
                    case 'edit':
                        return $this->post_edit();
                    case 'delete':
                        return $this->post_delete();
                    case 'category':
                        return $this->post_category();
                    default:
                        break;
                }
            } else {
                $posts = $this->getEntityManager()->find(Page::class, ['page_type' => 'post', 'status' => $param['post_type']]);
            }
        } else {
            $posts = $this->getEntityManager()->find(Page::class, ['page_type' => 'post']);
        }
        $publish = $this->getEntityManager()->callFunction('getCountPage', ['post', 'publish']);
        $draft = $this->getEntityManager()->callFunction('getCountPage', ['post', 'draft']);
        $post_count = $this->getEntityManager()->callFunction('getCountPage', ['post', '']);
        $view->setVariables([
            'posts' => $posts,
            'publish' => $helper->get_array_value($publish->getArrayCopy()),
            'draft' => $helper->get_array_value($draft->getArrayCopy()),
            'post_count' => $helper->get_array_value($post_count->getArrayCopy()),
            'query' => isset($param) ? $param : null
        ]);
        return $view;
    }

    protected function post_add(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Beiträge › Erstellen'
        ]));
        $view->setTemplate('dashboard/post/add');
        if ($request->isPost()) {
            $formData = $request->getPost();
            $author = (new Auth)->getAdminAuth();

            Logger::Debug(json_encode($formData));

            $this->getEntityManager()->insert(Page::class, [
                'title' => $formData['title'],
                'name' => $formData['name'],
                'guid' => $formData['guid'],
                'content' => $formData['content'],
                'status' => $formData['status'],
                'keywords' => $formData['keywords'],
                'options'=>'[]',
                'page_type' => 'post',
                'created' => time(),
            ]);
            $post = $this->getEntityManager()->findByOne(Page::class, ['name' => $formData['name'], 'page_type' => 'post']);
            $this->getEntityManager()->insert(Meta::class, ['value' => $formData['layout'], 'page_id' => $post->__get('id'), 'key' => 'layout']);
            if (isset($formData['category'])) {
                $this->getEntityManager()->insert(Meta::class, ['value' => json_encode($formData['category']), 'page_id' => $post->__get('id'), 'key' => 'category']);
            } else if (!isset($formData['category'])) {
                $category = $this->getEntityManager()->findByOne(Category::class, ['page_type' => 'post'], ['id' => 'ASC']);
                $this->getEntityManager()->insert(Meta::class, ['value' => json_encode([$category->__get('id') => $category->__get('name')]), 'page_id' => $post->__get('id'), 'key' => 'category']);
            }

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Page::class, ['img' => '', 'thumb' => ''], ['id' => $post->__get('id')]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);
                    
                    $host = Config::getConfig('host');
                    
                    $fb_photo_link=$http_protocol."://".$host."source/images/".$tmp_name;
                    $this->getEntityManager()->update(Page::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $post->__get('id')]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);

                    $system_config=new SystemConfig();
                    $image=new Image();

                    $image->resize(ROOT_DIR.'/public/source/images/'.$tmp_name, $system_config->getSetting('medium_size_w'), $system_config->getSetting('medium_size_h'));
                    $image->resize(ROOT_DIR.'/public/source/images/thumbnail/'.$tmp_name, $system_config->getSetting('thumbnail_size_w'), $system_config->getSetting('thumbnail_size_h'));

                } else {
                    Logger::Error('Image no exist');
                }
            }

            if ($formData['fb_posten']==='on'){
                try {
                    $this->FBPost($formData, isset($fb_photo_link)?$fb_photo_link:null, $post->__get('id'));
                }
                catch(Exception $e){
                    Logger::Error($e->getMessage());
                }
            }

            (new Router())->redirect('dashboard', ['page_name' => 'post']);
        }
        $view->setVariables([
            'request' => $request->getPost()
        ]);
        return $view;
    }

    protected function post_edit(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Beiträge › Bearbeiten'
        ]));
        $view->setTemplate('dashboard/post/edit');
        $param = $request->getQuery();
        if ($request->getPost()) {
            $formData = $request->getPost();

            $this->getEntityManager()->update(Page::class, [
                'title' => $formData['title'],
                'name' => $formData['name'],
                'guid' => $formData['guid'],
                'content' => $formData['content'],
                'keywords' => $formData['keywords'],
                'status' => $formData['status'],
                'updated' => time(),
            ], [
                'id' => $param['post_id']
            ]);

            // Logger::Debug($formData['keywords']);

            if (isset($formData['category'])) {
                $this->getEntityManager()->update(Meta::class, ['value' => json_encode($formData['category'])], ['page_id' => $param['post_id'], 'key' => 'category']);
            } else if (!isset($formData['category'])) {
                $category = $this->getEntityManager()->findByOne('Core\Entity\Post_Category', ['page_type' => 'post'], ['id' => 'ASC']);
                $this->getEntityManager()->update(Meta::class, ['value' => json_encode([$category->__get('id') => $category->__get('name')])], ['page_id' => $param['post_id'], 'key' => 'category']);
            }

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Page::class, ['header_img' => '', 'thumb_img' => ''], ['id' => $param['post_id']]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/thumbnail/' . $tmp_name);
                    
                    $host = \Core\Config\Config::getConfig('host');
                    
                    $fb_photo_link=$http_protocol."://".$host."source/images/".$tmp_name;
                    $this->getEntityManager()->update(Page::class, ['img' => $host . "source/images/" . $tmp_name, 'thumb' => $host . "source/images/thumbnail/" . $tmp_name], ['id' => $param['post_id']]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);

                    $system_config=new SystemConfig();
                    $image=new Image();

                    $image->resize(ROOT_DIR.'/public/source/images/'.$tmp_name, $system_config->getSetting('medium_size_w'), $system_config->getSetting('medium_size_h'));
                    $image->resize(ROOT_DIR.'/public/source/images/thumbnail/'.$tmp_name, $system_config->getSetting('thumbnail_size_w'), $system_config->getSetting('thumbnail_size_h'));


                } else {
                    \Core\Log\Logger::Error('Image no exist');
                }
            }

            if ($formData['fb_posten']==='on'){
                try {
                    $this->FBPost($formData, isset($fb_photo_link)?$fb_photo_link:null, $post->__get('id'));
                }
                catch(Exception $e){
                    Logger::Error($e->getMessage());
                }
            }
        }
        $post = $this->getEntityManager()->findByOne(Page::class, ['id' => $param['post_id']]);
        $info = $this->getEntityManager()->find(Meta::class, ['page_id' => $post->__get('id')]);
        $view->setVariables([
            'post' => $post,
            'info' => $info
        ]);
        return $view;
    }

    protected function post_delete(){
        $router = new Router();
        $request = new Request();
        $param = $request->getQuery();
        $host = Config::getConfig('host');
        $page = $this->getEntityManager()->findByOne(Page::class, ['id' => $param['post_id']]);
        if ($page->__get('img') !== null || $page->__get('img') !== '') {
            $header_img = str_replace($host, '', $page->__get('img'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $header_img);
        }
        if ($page->__get('thumb') !== null || $page->__get('thumb') !== '') {
            $thumb_img = str_replace($host, '', $page->__get('thumb'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $thumb_img);
        }
        $this->getEntityManager()->remove(Meta::class, ['page_id' => $param['post_id']]);
        // $this->getEntityManager()->remove('Core\Entity\Comments', ['post_id' => $param['post_id']]);
        $this->getEntityManager()->remove(Page::class, ['id' => $param['post_id']]);
        $router->redirect('dashboard', ['page_name' => 'post']);
    }

    protected function post_category() {
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Beiträge › Kategorie'
        ]));
        $view->setTemplate('dashboard/post/category');
        $param = $request->getQuery();
        if ($request->isPost()) {
            $formData = $request->getPost();
            $name = str_replace(' ', '_', $formData['name']);
            $category = [
                'name' => strtolower($name),
                'title' => $formData['name'],
                'description' => $formData['description'],
                'page_type' => 'post'
            ];
            if (!isset($formData['id'])) {
                $this->getEntityManager()->insert(Category::class, $category);
            } else {
                $this->getEntityManager()->update(Category::class, $category, ['id' => $formData['id']]);
            }
        } else if (isset($param['action']) && $param['action'] === 'delete') {
            $this->getEntityManager()->remove(Category::class, ['id' => $param['cat_id']]);
        } else if (isset($param['cat_id'])) {
            $cat_edit = $this->getEntityManager()->find(Category::class, ['id' => $param['cat_id']]);
            $view->setVariables([
                'cat_edit' => $cat_edit
            ]);
        }
        $liste = $this->getEntityManager()->find(Category::class, ['page_type' => 'post']);
        $view->setVariables([
            'liste' => $liste
        ]);
        return $view;
    }

    /*************************************************************** */

    public function teamAction(){
        $this->checkAdmin();
        $view = new ViewModel();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Team › Alle'
        ]));
        $helper = new Helper();
        $request = new Request();
        if ($request->isQuery()) {
            $param = $request->getQuery();
            if (isset($param['type']) && $param['type'] !== '') {
                switch ($param['type']) {
                    case 'add':
                        return $this->team_add();
                    case 'edit':
                        return $this->team_edit();
                    case 'delete':
                        return $this->team_delete();
                    default:
                        break;
                }
            }
        } else {
            $teams = $this->getEntityManager()->find(Team::class);
        }
        $view->setVariables([
            'teams' => $teams,
            'query' => isset($param) ? $param : null
        ]);
        return $view;
    }

    public function team_add(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Team › Erstellen'
        ]));
        $view->setTemplate('dashboard/team/add');
        if ($request->isPost()) {
            $formData = $request->getPost();

            $this->getEntityManager()->insert(Team::class, [
                'name' => $formData['name'],
                'position' => $formData['position'],
                'social' => json_encode($formData['social']),
                'phone' => $formData['phone'],
                'email' => $formData['email'],
            ]);

            $post = $this->getEntityManager()->findByOne(Team::class, ['name' => $formData['name']]);

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Team::class, ['image' => ''], ['id' => $post->__get('id')]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    $host = Config::getConfig('host');
                    $this->getEntityManager()->update(Team::class, ['image' => $host . "source/images/" . $tmp_name], ['id' => $post->__get('id')]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                } else {
                    \Core\Log\Logger::Error('Image no exist');
                }
            }
            (new Router())->redirect('dashboard', ['page_name' => 'team']);
        }
        $view->setVariables([
            'request' => $request->getPost()
        ]);
        return $view;
    }

    public function team_edit(){
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Team › Bearbeiten'
        ]));
        $view->setTemplate('dashboard/team/edit');
        $param = $request->getQuery();
        if ($request->getPost()) {
            $formData = $request->getPost();

            $this->getEntityManager()->update(Team::class, [
                'name' => $formData['name'],
                'position' => $formData['position'],
                'social' => json_encode($formData['social']),
                'phone' => $formData['phone'],
                'email' => $formData['email']
            ], [
                'id' => $param['id']
            ]);

            if (isset($formData['no_header_img']) && $formData['no_header_img'] === 'on') {
                $this->getEntityManager()->update(Team::class, ['image' => ''], ['id' => $param['id']]);
            } else if ($formData['file_name_img'] !== '') {
                if (!is_executable(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'])) {
                    $path_info = pathinfo(ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    $tmp_name = md5($formData['file_name_img'] . time()) . '.' . $path_info['extension'];
                    shell_exec('cp ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img'] . ' ' . ROOT_DIR . '/public/source/images/' . $tmp_name);
                    $host = Config::getConfig('host');
                    $this->getEntityManager()->update(Team::class, ['image' => $host . "source/images/" . $tmp_name], ['id' => $param['id']]);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/medium/' . $formData['file_name_img']);
                    shell_exec('rm ' . ROOT_DIR . '/public/source/upload/tmp_image/thumbnail/' . $formData['file_name_img']);
                } else {
                    Logger::Error('Image no exist');
                }
            }
        }
        $team = $this->getEntityManager()->findByOne(Team::class, ['id' => $param['id']]);
        $view->setVariables([
            'team' => $team,
        ]);
        return $view;
    }

    public function team_delete() {
        $router = new Router();
        $request = new Request();
        $param = $request->getQuery();
        $host = Config::getConfig('host');
        $team = $this->getEntityManager()->findByOne(Team::class, ['id' => $param['id']]);
        if ($team->__get('image') !== null || $team->__get('image') !== '') {
            $header_img = str_replace($host, '', $team->__get('image'));
            shell_exec('rm ' . ROOT_DIR . '/public/' . $header_img);
        }
        $this->getEntityManager()->remove(Team::class, ['id' => $param['id']]);
        $router->redirect('dashboard', ['page_name' => 'team']);
    }

    public function team_category(){}

    /*************************************************************** */

    

    /************************************************************** */

    

    /************************************************************** */

    

    /*************************************************************** */

    public function settingsAction() {
        $this->checkAdmin();
        $view = new ViewModel();
        $helper = new Helper();
        $request = new Request();
        if ($request->isQuery()) {
            $param = $request->getQuery();
            if (isset($param['type']) && $param['type'] !== '') {
                $type = 'setting_' . $param['type'];
                if (is_callable([$this, $type])) {
                    return $this->$type();
                } else { }
            } else { }
        } else { }
        return $view;
    }

    protected function setting_general() {
        $view = new ViewModel();
        $view->setTemplate('dashboard/settings/general');
        $request = new Request();
        $helper=new Helper();

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Einstellungen › Allgemein'=>''
            ],
        ]);

        $general = $this->setting['general'];

        if ($request->isPost()) {
            $data = $request->getPost();
            foreach ($general as $title) {
                if (isset($data[$title])) {
                    $this->getEntityManager()->update(Setting::class, ['value' => $data[$title]], ['key' => $title]);
                } else if ($title === 'users_can_register') {
                    $this->getEntityManager()->update(Setting::class, ['value' => '0'], ['key' => $title]);
                }
            }
            $view->setVariables([
                'success' => true
            ]);
        }

        foreach ($general as $title) {
            $general[$title] = $this->getEntityManager()->findByOne(Setting::class, ['key' => $title])->__get('value');
        }

        $view->setVariables([
            'setting' => $general
        ]);

        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › Allgemein'
        ]));

        return $view;
    }

    protected function setting_medien() {
        $view = new ViewModel();
        $view->setTemplate('dashboard/settings/medien');
        $request = new Request();
        $medien = $this->setting['medien'];
        if ($request->isPost()) {
            $data = $request->getPost();
            foreach ($medien as $title) {
                if (isset($data[$title])) {
                    $this->getEntityManager()->update(Setting::class, ['value' => $data[$title]], ['key' => $title]);
                } else if ($title === 'thumbnail_crop') {
                    $this->getEntityManager()->update(Setting::class, ['value' => '0'], ['key' => $title]);
                }
            }
            $view->setVariables([
                'success' => true
            ]);
        }
        foreach ($medien as $title) {
            $medien[$title] = $this->getEntityManager()->findByOne(Setting::class, ['key' => $title])->__get('value');
        }
        $view->setVariables([
            'setting' => $medien
        ]);
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › Medien'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Einstellungen › Medien'=>''
            ],
        ]);

        return $view;
    }

    protected function setting_discussion() {
        $view = new ViewModel();
        $view->setTemplate('dashboard/settings/discussion');
        $request = new Request();
        $discussion = $this->setting['discussion'];
        if ($request->isPost()) {
            $data = $request->getPost();
            foreach ($discussion as $title) {
                if (isset($data[$title])) {
                    $this->getEntityManager()->update(Setting::class, ['value' => $data[$title]], ['key' => $title]);
                } else if (
                    $title === 'comment_whitelist' ||
                    $title === 'comment_moderation' ||
                    $title === 'moderation_notify' ||
                    $title === 'comments_notify' ||
                    $title === 'comment_registration' ||
                    $title === 'require_name_email' ||
                    $title === 'default_comment_status'
                ) {
                    $this->getEntityManager()->update(Setting::class, ['value' => '0'], ['key' => $title]);
                }
            }
            $view->setVariables([
                'success' => true
            ]);
        }
        foreach ($discussion as $title) {
            $discussion[$title] = $this->getEntityManager()->findByOne(Setting::class, ['key' => $title])->__get('value');
        }
        $view->setVariables([
            'setting' => $discussion
        ]);
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › Diskussion'
        ]));

        return $view;
    }

    protected function setting_reading() {
        $view = new ViewModel();
        $request = new Request();
        $view->setTemplate('dashboard/settings/reading');
        $reading = $this->setting['reading'];
        if ($request->isPost()) {
            $data = $request->getPost();
            foreach ($reading as $title) {
                $this->getEntityManager()->update(Setting::class, ['value' => $data[$title]], ['key' => $title]);
            }
            $view->setVariables([
                'success' => true
            ]);
        }

        $access_token=$this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_access_token'])->__get('value');
        $page_id=$this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_page_id'])->__get('value');
        $fb = new Facebook([
            'app_id' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_id'])->__get('value'),
            'app_secret' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_secret'])->__get('value')
        ]);
        $response=$fb->get('/'.$page_id.'?fields=access_token', $access_token);
        $page_access_token=$response->getDecodedBody()['access_token'];

        $inst_page_id=$fb->get('/'.$page_id.'?fields=instagram_business_account', $page_access_token)->getDecodedBody()['instagram_business_account']['id'];
        //  https://www.goldstadt-invest.de/wp-content/uploads/2018/01/cache_1768532.png

        $inst_response=$fb->get('/'.$inst_page_id.'/media', $page_access_token);

        $view->setVariables([
            'response'=>$inst_response->getDecodedBody()
        ]);


        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › Lesen'
        ]));

        return $view;
    }

    protected function setting_social() {
        $view = new ViewModel();
        $request = new Request();
        $view->setTemplate('dashboard/settings/social');
    
        $social = $this->setting['social'];
        if ($request->isPost()) {
            $data = $request->getPost();
            foreach ($social as $title) {
                $this->getEntityManager()->update(Setting::class, ['value' => $data[$title]], ['key' => $title]);
            }
            $view->setVariables([
                'success' => true
            ]);
        }

        $access_token=$this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_access_token'])->__get('value');
        if (isset($data['fb_page_id']) && $data['fb_page_id']!=='' && $access_token!==''){
            $page_id=$data['fb_page_id'];
            $fb = new Facebook([
                'app_id' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_id'])->__get('value'),
                'app_secret' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_secret'])->__get('value')
            ]);
            $response=$fb->get('/'.$page_id.'?fields=access_token', $access_token);
            $page_access_token=$response->getDecodedBody();
            if (isset($page_access_token['access_token'])) {
                $this->getEntityManager()->update(Setting::class, ['value' => $page_access_token['access_token']], ['key' => 'fb_page_access_token']);
            }
            
            $inst_page_id=$fb->get('/'.$page_id.'?fields=instagram_business_account', $page_access_token['access_token'])->getDecodedBody();
            if (isset($inst_page_id['instagram_business_account']['id'])){
                $this->getEntityManager()->update(Setting::class, ['value' => $inst_page_id['instagram_business_account']['id']], ['key' => 'inst_page_id']);
            }
            
        }

        foreach ($social as $title) {
            $social[$title] = $this->getEntityManager()->findByOne(Setting::class, ['key' => $title])->__get('value');
        }
        $view->setVariables([
            'setting' => $social
        ]);

        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › Sozialnetzwerk'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Einstellungen › Sozialnetzwerk'=>''
            ],
        ]);

        return $view;
    }

    protected function setting_mail() {
        $view = new ViewModel();
        $view->setTemplate('dashboard/settings/mail');
        $request = new Request();

        if ($request->isPost()) {
            $data = $request->getPost();
            if ($data['transport'] === 'smtp') {
                $data['mail_from'] = $data['user'];
            } else {
                foreach ($data as $key => $value) {
                    if ($key !== 'transport' && $key !== 'mail_from') {
                        $data[$key] = "";
                    }
                }
            }
            // {"transport":"smtp","host":"smtp.strato.de","port":"465","user":"reply@it-media-solutions.de","password":"DidPvC01","mail_from":"noreply@it-media-solutions.de","ssl":"ssl"}
            $this->getEntityManager()->update(Setting::class, ['value' => json_encode($data)], ['key' => 'smtp']);
            $view->setVariables([
                'success' => true
            ]);
        }
        $smtp = json_decode($this->getEntityManager()->findByOne(Setting::class, ['key' => 'smtp'])->__get('value'), true);
        $view->setVariables([
            'setting' => $smtp
        ]);
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Einstellungen › E-Mail'
        ]));

        $view->setLayoutVariables([
            'breadcrumb'=>[
                'Einstellungen › E-Mail'=>''
            ],
        ]);
        return $view;
    }

    protected function setting_thema(){}

    /*************************************************************** */

    public function commentsAction() {
        $this->checkAdmin();
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Kommentare'
        ]));

        if ($request->isQuery()) {
            $view->setVariable('query', $request->getQuery());
        }

        if ($request->isQuery() && isset($request->getQuery()['status'])) {
            $comments = $this->getEntityManager()->find(Comments::class, ['approved' => $this->comment_status[$request->getQuery()['status']]]);
        } else if ($request->isQuery() && isset($request->getQuery()['post_id'])) {
            $comments = $this->getEntityManager()->find(Comments::class, ['post_id' => $request->getQuery()['post_id']]);
        } else {
            $comments = $this->getEntityManager()->findAll(Comments::class);
        }

        $comment_count = $this->getEntityManager()->executeQuery('SELECT count(id) as `count` FROM `comments`', stdObject::class);
        $approved_count = $this->getEntityManager()->executeQuery('SELECT count(id) as `count` FROM `comments` WHERE `approved`=1', stdObject::class);
        $not_approved_count = $this->getEntityManager()->executeQuery('SELECT count(id) as `count` FROM `comments` WHERE `approved`=0', stdObject::class);

        $view->setVariables([
            'comments' => $comments,
            'comment_count' => $comment_count->get('count'),
            'approved_count' => $approved_count->get('count'),
            'not_approved_count' => $not_approved_count->get('count'),
        ]);

        return $view;
    }

    public function instagramAction(){}
    
    /*************************************************************** */

    protected function FBPost($formData, $fb_photo_link=null, $post_id){
        $access_token=$this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_access_token'])->__get('value');
        $page_id=$this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_page_id'])->__get('value');
        $fb = new Facebook([
            'app_id' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_id'])->__get('value'),
            'app_secret' => $this->getEntityManager()->findByOne(Setting::class, ['key' => 'fb_app_secret'])->__get('value')
        ]);
        $response=$fb->get('/'.$page_id.'?fields=access_token', $access_token);
        $page_access_token=$response->getDecodedBody()['access_token'];
        if (isset($fb_photo_link) && $fb_photo_link!==null){
            $photo_response=$fb->post('/'.$page_id.'/photos', [
                'caption'=>$formData['title'],
                'url'=>$fb_photo_link,
                'published'=>false
            ], $page_access_token);
        }
        $hashtag="";
        foreach(json_decode($formData['keywords']) as $key){
            $hashtag.="#".$key->tag." ";
        }
        $data=[
            'message'=>utf8_encode(str_replace("&nbsp;", " ",utf8_encode(strip_tags($formData['content']))))." ".$hashtag." ".$formData['guid']
        ];
        if (isset($photo_response)){
            $data['attached_media[0]']='{"media_fbid":"'.$photo_response->getDecodedBody()['id'].'"}';
        }
        $fb_post=$fb->post('/'.$page_id.'/feed', $data, $page_access_token);
        $this->getEntityManager()->update(Page::class, ['fbid'=>$fb_post->getDecodedBody()['id']], ['id'=>$post_id]);
    }

    public function logsAction() {
        $this->checkAdmin();
        $view = new ViewModel();
        $request = new Request();
        $view->setHeader((new Helper())->getHeader([
            'title' => 'Logs › Viewer'
        ]));
        if ($request->isQuery()) {
            $type = $request->getQuery()['type'];
            $date = $request->getQuery()['date'];
            $dir = ROOT_DIR . '/data/' . $type . '/';
            $dir_hndl = opendir($dir);
            while (false !== ($name = readdir($dir_hndl))) {
                if (!is_file($dir)) {
                    if ($name === '.' || $name === '..') { } else {
                        if (isset($date) && $date !== '' && stristr($name, $date) !== false) {
                            $file = $dir . "/" . $name;
                            $mime = mime_content_type($file);
                            $file_liste[] = [
                                'name' => $name,
                                'latest_update' => date("d.m.Y H:i", filemtime($file)),
                                'full_path' => $file,
                            ];
                        } else if (!isset($date) || $date === '') {
                            $file = $dir . "/" . $name;
                            $mime = mime_content_type($file);
                            $file_liste[] = [
                                'name' => $name,
                                'latest_update' => date("d.m.Y H:i", filemtime($file)),
                                'full_path' => $file,
                            ];
                        }
                    }
                }
            }
            closedir($dir_hndl);
            $view->setVariables([
                'file_liste' => $file_liste,
                'request' => $request->getQuery()
            ]);
        }
        return $view;
    }

}