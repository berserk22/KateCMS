<?php

namespace Core\Helper;

use Core\MySQL\PDOMySQL;
use Core\Log\Logger;
use Core\Entity\Setting;
use Core\Config\Config;

class Helper {

    protected $em;

    protected $header;
    
    public function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    public function getSetting($key=null){
        if ($key!==null){
            return $this->getEntityManager()->findByOne(Setting::class, ['key'=>$key])->__get('value');
        }
        else {
            return $this->getEntityManager()->findAll(Setting::class);
        }
    }

    protected function getConfig($param){
        return Config::getConfig($param);
    }

    public function getHost(){
        return $this->getConfig('host');
    }

    public function getSiteTitle(){
        $title=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'title']);
        if ($title===false){
            $title='';
        }
        else {
            $title=' | '.$title->__get('value');
        }
        return $title;
    }

    public function getHeader(array $option=[]){
        $header=[];
        if (isset($option['title'])){
            $header[]=[
                'title'=>$option['title'].$this->getSiteTitle()
            ];
            $header[]=[
                'meta'=>[
                    'itemprop'=>'name',
                    'content'=>$option['title'].$this->getSiteTitle()
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'name'=>'twitter:title',
                    'content'=>$option['title'].$this->getSiteTitle()
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:title',
                    'content'=>$option['title'].$this->getSiteTitle()
                ]
            ];
        }
        else {
            $title= str_replace(' | ', '', $this->getSiteTitle());
            
            $header[]=[
                'meta'=>[
                    'itemprop'=>'name',
                    'content'=>$title
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'name'=>'twitter:title',
                    'content'=>$title
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:title',
                    'content'=>$title
                ]
            ];
        }
        
        if (isset($option['description'])){
            $tmp_str=explode(' ', str_replace("&nbsp;", " ",strip_tags($option['description'])));
            $count=20;
            $str="";
            foreach ($tmp_str as $key => $value) {
                if ($key===$count) break;
                else $str.=$value." ";
            }
            
            $header[]=[
                'meta'=>[
                    'name'=>'description',
                    'content'=>$str
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'itemprop'=>'description',
                    'content'=>$str
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'name'=>'twitter:description',
                    'content'=>$str
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:description',
                    'content'=>$str
                ]
            ];
        }
        
        if (isset($option['img']) && $option['img']!==null){
            $host=$this->getHost();
            $tmp_path=str_replace($host, ROOT_DIR."/public/", $option['img']);

            list($width, $height)= getimagesize($tmp_path);
            
            $header[]=[
                'meta'=>[
                    'name'=>'image',
                    'content'=>$option['img']
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'itemprop'=>'image',
                    'content'=>$option['img']
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'name'=>'twitter:image:src',
                    'content'=>$option['img']
                ]
            ];
            
            
            $header[]=[
                'meta'=>[
                    'name'=>'twitter:image:alt',
                    'content'=>$option['title']
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:image',
                    'content'=>$option['img']
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:image:alt',
                    'content'=>$option['title']
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:image:width',
                    'content'=>$width
                ]
            ];
            
            $header[]=[
                'meta'=>[
                    'property'=>'og:image:height',
                    'content'=>$height
                ]
            ];
        }
        
        if (isset($option['url'])){
            $header[]=[
                'meta'=>[
                    'property'=>'og:url',
                    'content'=>$option['url']
                ]
            ];
            
            $header[]=[
                'link'=>[
                    'rel'=>'canonical',
                    'href'=>$option['url']
                ]
            ];
        }        
        
//        $header[]=[
//            'meta'=>[
//                'property'=>'fb:admins',
//                'content'=> ''
//            ]
//        ];

//        $header[]=[
//            'meta'=>[
//                'property'=>'fb:app_id',
//                'content'=> ''
//            ]
//        ];

        $header[]=[
            'meta'=>[
                'property'=>'og:type',
                'content'=> 'article'
            ]
        ];
        
        
        if (isset($option['keywords'])){
            $keywords='';
            foreach (json_decode($option['keywords'], true) as $key => $keyword) {
                $keywords===''?$keywords=$keyword['tag']:$keywords.=', '.$keyword['tag'];
            }
            $header[]=[
                'meta'=>[
                    'name'=>'keywords',
                    'content'=>$keywords
                ]
            ];
        }
        //$header[]=[
        //    'meta'=>[
        //        'property'=>'og:locale',
        //        'content'=>'de_DE'
        //    ]
        //];
        $header[]=[
            'meta'=>[
                'property'=>'og:site_name',
                'content'=>str_replace(' | ', '', $this->getSiteTitle())
            ]
        ];
        $header[]=[
            'meta'=>[
                'name'=>'twitter:card',
                'content'=>'summary_large_image'
            ]
        ];
//        $header[]=[
//            'meta'=>[
//                'name'=>'twitter:site',
//                'content'=>'@'
//            ]
//        ];
//        $header[]=[
//            'meta'=>[
//                'name'=>'twitter:creator',
//                'content'=>'@'
//            ]
//        ];
                        
        return $header;
        
    }

    public function get_array_value(array $array){
        if (count($array)===1){
            foreach ($array as $value) {
                $tmp_value=$value;
            }
            return $tmp_value;
        }
    }
}