<?php

namespace Api\Controller;

use Core\AbstractApiController;
use Core\Model\JsonModel;
use Core\Model\ViewModel;
use Core\MySQL\PDOMySQL;
use Core\Http\Request;
use Core\File\FileUpload;
use Core\Api\ImageUpload;
use Core\Config\SystemConfig;
use Core\Log\Logger;
use Core\Helper\Helper;
use Core\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;

use Core\Http\Session;

use App\Entity\Page;
use App\Entity\Comments;

class ApiController extends AbstractApiController {
    
    protected $em;
        
    public function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    public function getParam($param){
        return (new SystemConfig())->getSetting($param);
    }
    
    public function indexAction() {
        $view=new JsonModel();
        $view->setVariables(["server"=>"SAPSv2 RESTfull Web Service"]);
        return $view;
    }
    
    public function testAction() {
        $view=new JsonModel();

        $session=new Session();

        $view->setVariables([
            'test'=>'Test Value',
            'sessionID'=>$session->getSessionId()
        ]);

        //var_dump($view->getVariables());

        return $view;
    }
    
    public function checkNameAction(){
        $view=new JsonModel();
        $request=new Request();
        $data=$request->getPost();
        $data['name']=str_replace(['ü', 'ö', 'ä', 'ß', '&'], ['ue', 'oe', 'ae', 'ss', ''], $data['name']);
        $post=$this->getEntityManager()->findByOne(Page::class, $data);
        if (is_object($post)){
            $count=1;
            while (is_object($post)){
                if ($data['id']===$post->__get('id') && $post->__get('name')!==''){
                    $view->setVariables(['result'=>true, 'name'=>$data['name']]);
                }
                else {
                    $count++;
                    $data['name']=$data['name'].'_'.$count;
                    $post=$this->getEntityManager()->findByOne(Page::class, $data);
                }
            }
            $view->setVariables(['result'=>true, 'name'=>$data['name']]);
        }
        else {
            $view->setVariables(['result'=>true, 'name'=>$data['name']]);
        }     
        return $view;
    }    
    
    public function uploadImageAction(){
        $view=new JsonModel();
        
        $host=Config::getConfig('host');
        
        $fileUploads=new FileUpload([
            'script_url'=>$http_protocol.'://'.$host.'api/uploadImage/',
            'accept_file_types' => '/\.(jpe?g|bmp|png)$/i',
            'upload_dir'=>ROOT_DIR.'/public/source/upload/tmp_image/',
            'upload_url'=>ROOT_DIR.'/public/source/upload/tmp_image/',
            'param_name'=>'header_img',
            'image_versions'=>[
                'medium'=>[
                    'max_width' => $this->getParam('medium_size_w'),
                    'max_height' => $this->getParam('medium_size_h')
                ],
                'thumbnail'=>[
                    'max_width' => $this->getParam('thumbnail_size_w'),
                    'max_height' => $this->getParam('thumbnail_size_h')
                ]
            ]
        ]);   
        Logger::Debug(json_encode($fileUploads->response));
        $view->setVariables($fileUploads->response);
        return $view;
    }
    
    public function getLogFileAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $data=$request->getPost();
            if (isset($data['file'])){
                $file=file_get_contents(ROOT_DIR.'/data/'.$data['type'].'/'.$data['file']);                
                $view->setVariables([
                    'file'=>$data['file'],
                    'content'=>'<pre class="mb-0 mx-2">'.$file.'</pre>'
                ]);
            }
            else {
                $view->setStatusCode(400);
            }
        }
        return $view;
    }
    
    public function addCommentAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $formData=$request->getPost();
            if ($formData['author']!=='' && $formData['email']!==''){
                $this->getEntityManager()->insert(Comments::class, [
                    'page_id'=>$formData['page_id'],
                    'author'=>$formData['author'],
                    'email'=>$formData['email'],
                    'created'=>time(),
                    'content'=>str_replace("\n", "<br>", $formData['content']),
                    'approved'=>0
                ]);
            }
        }
        else {
            $view->setStatusCode(400);
        }
        return $view;
    }
    
    public function approvedCommentAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $query=$request->getPost();
            $this->getEntityManager()->update(Comments::class, ['approved'=>1], ['id'=>$query['comment_id']]);
        }
        return $view;
    }
    
    public function removeCommentAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $query=$request->getPost();
            $this->getEntityManager()->remove(Comments::class, ['id'=>$query['comment_id']]);
        }
        return $view;
    }
    
    /*  Korregieren  */
    public function getComments(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $query=$request->getPost();
            
            if (isset($query['post_id'])){
                $comment=$this->getEntityManager()->find('Core\Entity\Comments', ['post_id'=>$query['post_id']], null, ['id'=>'DESC']);
            }
            
            if (isset($comment)){}
            else {
                $view->setStatusCode(400);
            }
        }
        return $view;
    }
    
    public function getMostClientAction(){
        $view=new JsonModel();
        $start=strtotime(date("d.m.Y", time()))-(86400*7);
        $end=strtotime(date("d.m.Y", time()))+86399;
        $clients=$this->getEntityManager()->executeQuery("SELECT count(id) as count_client, client FROM views where time_view>".$start." and time_view<".$end." group by client;", \Core\Utils\stdObject::class);
        if (!is_object($clients)){
            foreach ($clients as $key => $client) {
                $period[$client->get('client')]=$client->get('count_client');
            }
        }
        else {
            $period[$clients->get('client')]=$clients->get('count_client');
        }
        $view->setVariables($period);
        return $view;
    }
    
    public function getMostOSAction(){
        $view=new JsonModel();
        $start=strtotime(date("d.m.Y", time()))-(86400*7);
        $end=strtotime(date("d.m.Y", time()))+86399;
        $platforms=$this->getEntityManager()->executeQuery("SELECT count(id) as count_os, os FROM views where time_view>".$start." and time_view<".$end." group by os;", \Core\Utils\stdObject::class);
        if (!is_object($platforms)){
            foreach ($platforms as $key => $platform) {
                $period[$platform->get('os')]=$platform->get('count_os');
            }
        }
        else {
            $period[$platforms->get('os')]=$platforms->get('count_os');
        }
        $view->setVariables($period);
        return $view;
    }
        
    public function getRegionAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isQuery()){
            if (isset($request->getQuery()['state'])){
                $state=$request->getQuery()['state'];
                $region_liste=$this->getEntityManager()->find('Core\Entity\Region', ['id_state'=>$state]);
                $tmp_array=[];
                foreach ($region_liste as $key => $region) {
                    $tmp_array[]=$region->getArrayCopy();
                }
                $view->setVariables([
                    'region_liste'=>$tmp_array
                ]);
            }
        }
        return $view;
    }
    
    public function getCityAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isQuery()){
            if (isset($request->getQuery()['state']) && isset($request->getQuery()['region'])){
                $data=$request->getQuery();
                $city_liste=$this->getEntityManager()->find('Core\Entity\City', ['id_state'=>$data['state'], 'id_region'=>$data['region']]);
                $tmp_array=[];
                if (is_object($city_liste)){
                    $tmp_array[]=$city_liste->getArrayCopy();
                }
                else {
                    foreach ($city_liste as $key => $city) {
                        $tmp_array[]=$city->getArrayCopy();
                    }
                }
                    
                $view->setVariables([
                    'city_liste'=>$tmp_array
                ]);
            }
        }
        return $view;
    }

    public function contactAction(){
        $view=new JsonModel();
        $request=new Request();
        $helper=new Helper();
        if ($request->isPost()){
            $formData=$request->getPost();
            $view_mail=new ViewModel();
            $view_mail->setTemplate('mail/index');
            $view_mail->setVariables([
                'email'=>$formData['email'],
                'subject'=>$formData['subject'],
                'name'=>$formData['name'],
                'content'=>$formData['content']
            ]);

            $smtp_setting=json_decode($helper->getSetting('smtp'), true);
            $mail=new PHPMailer();
            $mail->isSMTP();
            $mail->SMTPSecure = $smtp_setting['ssl'];
            $mail->SMTPAutoTLS = false;
            $mail->Host = $smtp_setting['host'];
            $mail->Port = $smtp_setting['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_setting['user'];
            $mail->Password = $smtp_setting['password'];
            $mail->setFrom($smtp_setting['mail_from']);
            $mail->addAddress($helper->getSetting('admin_email'));
            $mail->addReplyTo($formData['email'], $formData['name']);
            $mail->Subject=$formData['subject'];
            $mail->msgHTML($view_mail->renderPartial());
            if (!$mail->send()) {
                Logger::ErrorReport('Mailer Error: ' . $mail->ErrorInfo);
            } else {
                Logger::Info('Message sent! From: '.$formData['email']);
            }
        }
        else {
            $view->setStatusCode(400);
        }        
        return $view;
    }
    
    public function addAttrAction(){
        $view=new JsonModel();
        $request=new Request();
        
        if ($request->isPost()){
            $data=$request->getPost();
            
            if (isset($data['name'])){
                $this->getEntityManager()->insert('Core\Entity\Immo_Attr', ['name'=>$data['name']]);
                $view->setVariable('new_attr', $data['name']);
            }
        }
        
        return $view;
    }
    
    public function addCityAction(){
        $view=new JsonModel();
        $request=new Request();
        if ($request->isPost()){
            $data=$request->getPost();
            $this->getEntityManager()->insert('Core\Entity\City', [
                'id_state'=>$data['add_state'],
                'id_region'=>$data['add_region'],
                'name'=>$data['add_city'],
                'zip'=>$data['add_zip']
            ]);
            $view->setVariables([
                'city'=>[
                    'name'=>$data['add_city'],
                    'zip'=>$data['add_zip']
                ]
            ]);
        }        
        return $view;
    }
    
    public function addEventAction(){
        $view=new JsonModel();
        $html_view=new ViewModel();
        $html_view->setTemplatePath('dashboard');
        $html_view->setTemplate('dashboard/api/add-event');
                
        $request=new Request();
        if ($request->isPost()){
            $data=$request->getPost();
            
            $html_view->setVariables([
                'data'=>$data
            ]);
            
            $view->setVariables([
                'content'=>$html_view->renderPartial()
            ]);
        }        
        return $view;
    }
    
}
