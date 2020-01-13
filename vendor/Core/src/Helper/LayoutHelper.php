<?php

namespace Core\Helper;

use Core\Config\Config;
use Core\MySQL\PDOMySQL;
use Core\Entity\Setting;
use Core\Auth\Authentication as Auth;
use Core\Http\Request;
use Core\Http\Session;
use Core\Router\Router;
use Core\Helper\stdObject;

use App\Entity\Page;
use App\Entity\Page_Type;
use App\Entity\Users;
use App\Entity\Category;
use App\Entity\Meta;
use App\Entity\Attribute;
use App\Entity\Team;
use App\Entity\Partner;
use App\Entity\Comments;

class LayoutHelper {

    protected $em;

    protected $month=[
        'en'=>[
            '01'=>'January',
            '02'=>'February',
            '03'=>'March',
            '04'=>'April',
            '05'=>'May',
            '06'=>'June',
            '07'=>'July',
            '08'=>'August',
            '09'=>'September',
            '10'=>'October',
            '11'=>'November',
            '12'=>'December',
        ], 
        'de'=>[
            '01'=>'Januar',
            '02'=>'Februar',
            '03'=>'März',
            '04'=>'April',
            '05'=>'Mai',
            '06'=>'Juni',
            '07'=>'Juli',
            '08'=>'August',
            '09'=>'September',
            '10'=>'Oktober',
            '11'=>'November',
            '12'=>'Dezember',
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

    protected function getConfig($param){
        return Config::getConfig($param);
    }

    public function getHost(){
        return $this->getConfig('host');
    }

    public function getTitle(){
        return $this->getEntityManager()->findByOne(Setting::class, ['key'=>'title'])->__get('value');
    }

    public function getTemplateName(){
        return $this->getEntityManager()->findByOne(Setting::class, ['key'=>'theme'])->__get('value');
    }

    public function getDate($time, $with_time=false, $lang='de'){
        $date_format=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'date_format']);
        if ($date_format!==false) $date=date($date_format->__get('value'), $time);
        else $date=date("d.m.Y", $time);

        foreach($this->month['en'] as $month_key=>$month){
            if (is_string(strstr($date, $month))) $date=str_replace($month, $this->month[$lang][$month_key], $date);
        }
        if($with_time){
            $time_format=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'time_format']);
            if ($time_format!==false) $date.=", ".date($time_format->__get('value'), $time);
            else $date.=", ".date("H:i", $time);
        }
        return $date;
    }

    public function getDescription($description='', $word_count=null){
        if ($word_count===null){
            $word_count=(int)$this->getEntityManager()->findByOne(Setting::class, ['key'=>'desc_word_count'])->__get('value');
        }

        $tmp_str=explode(' ', str_replace("&nbsp;", " ",strip_tags($description)));
        $str="";

        foreach ($tmp_str as $key => $value) {
            if ($key===$word_count) break;
            else $str.=$value." ";
        }
        return $str."[...]";
    }

    public function getPage($page_id){
        return $this->getEntityManager()->findByOne(Page::class, ['id'=>$page_id]);
    }

    public function getCountComments($page_id){
        $comments_count=$this->getEntityManager()->executeQuery("SELECT count(comments.id) as comments_count FROM comments WHERE page_id=$page_id AND approved=1;", stdObject::class)->getArrayCopy();
        if (is_array($comments_count)){
            return $this->get_array_value($comments_count);
        }
        else {
            return 0;
        }
    }

    public function getComments($page_id){
        $comments=$this->getEntityManager()->find(Comments::class, ['page_id'=>$page_id, 'approved'=>1], null, ['id'=>'DESC']);
        if (is_array($comments) || is_bool($comments)){
            return $comments;
        }
        else return [$comments];
    }

    public function getLatest($page_type='post', $count=6){
        $liste=$this->getEntityManager()->find(Page::class, ['page_type'=>$page_type], [], ['id'=>'DESC'], [0, $count]);
        if (is_object($liste)){
            return [$liste];
        }
        else return $liste;
    }

    public function getCategory($page_type='post'){
        $category_liste=$this->getEntityManager()->find(Category::class, ['page_type'=>$page_type]);
        if (is_object($category_liste)){
            return [$category_liste];
        }
        else return $category_liste;
    }

    public function getPageCategory(int $page_id){
        $post_category=$this->getEntityManager()->findByOne(Meta::class, ['page_id'=>$page_id, 'key'=>'category']);
        $category_liste=[];        
        foreach(json_decode($post_category->__get('value'), true) as $key=>$value){
            $category=$this->getEntityManager()->findByOne(Category::class, ['id'=>$key]);
            $category_liste[]=$category;
        }
        return $category_liste;
    }

    public function getAttr($page_type){
        $attribute_liste=$this->getEntityManager()->find(Attribute::class, ['page_type'=>$page_type]);
        if (is_object($attribute_liste)){
            return [$attribute_liste];
        }
        else return $attribute_liste;
    }

    public function MetaToArray($postmeta){
        $meta=[];
        if (is_object($postmeta)){
            $meta[$postmeta->__get('key')]=$postmeta->__get('value');
        }
        else if (is_array($postmeta)) {
            foreach ($postmeta as $key => $value) {
                $meta[$value->__get('key')]=$value->__get('value');
            }
        }
        return $meta;
    }

    public function get_array_value($array=[]){
        if (count($array)===1){
            foreach ($array as $value) {
                $tmp_value=$value;
            }
            return $tmp_value;
        }
    }

    public function replace_point($sum){
        if ($sum===''){
            $sum=0;
        }
        $ost=strlen(strstr($sum, ".")) - 1;
        if ($ost!=-1) {
            if ($ost==1) {
                $sum=$sum."0";
            }
        }
        else if ($ost==-1) {
            $sum=$sum.".00";
        }
        return str_replace('.', ',', $sum);
    }

    public function getGMap(array $options=null, $title='', $description='', $marker='/images/gmap_marker_active.png'){
        $gapi_key=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'gapi_key']);
        if ($gapi_key!==false){
            $q='';
            if (isset($options['city']) && isset($options['street']))
                $q=$options['street'].', '.(isset($options['zip'])?$options['zip'].' ':'').$options['city'];
            
            $marker_css='<style>.google-map-markers {display: none;}.google-map-container {width: 100%;}.google-map {height: 250px;width: 100%;}@media (min-width: 576px) {.google-map {height: 250px;}}@media (min-width: 768px) {.google-map {height: 300px;}}@media (min-width: 992px) {.google-map {height: 350px;}}@media (min-width: 1200px) {.google-map {height: 650px;}}@media (min-width: 1200px) {.height-fill .google-map {position: absolute !important;top: 0;bottom: 0;height: auto;}}.gm-style .gm-style-iw{font-weight:300;font-size:13px;overflow:hidden;}.gm-style .gm-style-iw-a{position:absolute;width:9999px;height:0;}.gm-style .gm-style-iw-t{position:absolute;width:100%;}.gm-style .gm-style-iw-t::after{background:white;box-shadow:-2px 2px 2px 0 rgba(178,178,178,.4);content:"";height:15px;left:0;position:absolute;top:0;transform:translate(-50%,-50%) rotate(-45deg);width:15px;}.gm-style .gm-style-iw-c{position:absolute;box-sizing:border-box;overflow:hidden;top:0;left:0;transform:translate(-50%,-100%);background-color:white;border-radius:4px;padding:12px;box-shadow:0 2px 7px 1px rgba(0,0,0,0.3);}.gm-style .gm-style-iw-d{box-sizing:border-box;overflow:auto}.gm-style .gm-style-iw-d::-webkit-scrollbar{width:18px;height:12px;-webkit-appearance:none;}.gm-style .gm-style-iw-d::-webkit-scrollbar-track,.gm-style .gm-style-iw-d::-webkit-scrollbar-track-piece{background:#fff;}.gm-style .gm-style-iw-c .gm-style-iw-d::-webkit-scrollbar-thumb{background-color:rgba(0,0,0,0.12);border:6px solid transparent;border-radius:4px;background-clip:content-box;}.gm-style .gm-style-iw-c .gm-style-iw-d::-webkit-scrollbar-thumb:horizontal{border:3px solid transparent;}.gm-style .gm-style-iw-c .gm-style-iw-d::-webkit-scrollbar-thumb:hover{background-color:rgba(0,0,0,0.3);}.gm-style .gm-style-iw-c .gm-style-iw-d::-webkit-scrollbar-corner{background:transparent;}.gm-style-iw-d p {margin-top: 0;margin-bottom:10px;font-size: 14px;}.gm-style .gm-iw{color:#2c2c2c;}.gm-style .gm-iw b{font-weight:400;}.gm-style .gm-iw a:link,.gm-style .gm-iw a:visited{color:#4272db;text-decoration:none;}.gm-style .gm-iw a:hover{color:#4272db;text-decoration:underline;}.gm-style .gm-iw .gm-title{font-weight:400;margin-bottom:1px;}.gm-style .gm-iw .gm-basicinfo{line-height:18px;padding-bottom:12px;}.gm-style .gm-iw .gm-website{padding-top:6px;}.gm-style .gm-iw .gm-photos{padding-bottom:8px;-ms-user-select:none;-moz-user-select:none;-webkit-user-select:none;}.gm-style .gm-iw .gm-sv,.gm-style .gm-iw .gm-ph{cursor:pointer;height:50px;width:100px;position:relative;overflow:hidden;}.gm-style .gm-iw .gm-sv{padding-right:4px;}.gm-style .gm-iw .gm-wsv{cursor:pointer;position:relative;overflow:hidden;}.gm-style .gm-iw .gm-sv-label,.gm-style .gm-iw .gm-ph-label{cursor:pointer;position:absolute;bottom:6px;color:#fff;font-weight:400;text-shadow:rgba(0,0,0,0.7) 0 1px 4px;font-size:12px;}.gm-style .gm-iw .gm-stars-b,.gm-style .gm-iw .gm-stars-f{height:13px;font-size:0;}.gm-style .gm-iw .gm-stars-b{position:relative;background-position:0 0;width:65px;top:3px;margin:0 5px;}.gm-style .gm-iw .gm-rev{line-height:20px;-ms-user-select:none;-moz-user-select:none;-webkit-user-select:none;}.gm-style.gm-china .gm-iw .gm-rev{display:none;}.gm-style .gm-iw .gm-numeric-rev{font-size:16px;color:#dd4b39;font-weight:400;}.gm-style .gm-iw.gm-transit{margin-left:15px;}.gm-style .gm-iw.gm-transit td{vertical-align:top;}.gm-style .gm-iw.gm-transit .gm-time{white-space:nowrap;color:#676767;font-weight:bold;}.gm-style .gm-iw.gm-transit img{width:15px;height:15px;margin:1px 5px 0 -20px;float:left;}</style>';

            $gmap='
            <div id="map">
                <div class="google-map-container" data-key="'.$gapi_key->__get('value').'" data-center="'.$q.'" data-zoom="16">
                    <div class="google-map"></div>
                    <ul class="google-map-markers">
                        <li data-location="'.$q.'" data-description="'.$description.'" data-title="'.$title.'" data-icon="'.$marker.'" data-icon-active="'.$marker.'"></li>
                    </ul>
                </div>
            </div>';

            $gscript='<script>function initMaps(){for(var e,t=0;t<$(".google-map-container").length;t++)if($(".google-map-container")[t].hasAttribute("data-key")){e=$(".google-map-container")[t].getAttribute("data-key");break}$.getScript("//maps.google.com/maps/api/js?"+(e?"key="+e+"&":"")+"sensor=false&libraries=geometry,places&v=quarterly",function(){var e=document.getElementsByTagName("head")[0],t=e.insertBefore;e.insertBefore=function(o,a){o.href&&-1!==o.href.indexOf("//fonts.googleapis.com/css?family=Roboto")||-1!==o.innerHTML.indexOf("gm-style")||t.call(e,o,a)};for(var o=new google.maps.Geocoder,a=0;a<$(".google-map-container").length;a++){var n=parseInt($(".google-map-container")[a].getAttribute("data-zoom"),10)||11,i=$(".google-map-container")[a].hasAttribute("data-styles")?JSON.parse($(".google-map-container")[a].getAttribute("data-styles")):[],r=$(".google-map-container")[a].getAttribute("data-center")||"New York",g=new google.maps.Map($(".google-map-container")[a].querySelectorAll(".google-map")[0],{zoom:n,styles:i,scrollwheel:!1,center:{lat:0,lng:0}});$(".google-map-container")[a].map=g,$(".google-map-container")[a].geocoder=o,$(".google-map-container")[a].google=google,getLatLngObject(r,null,$(".google-map-container")[a],function(e,t,o){o.map.setCenter(e)});var c=$(".google-map-container")[a].querySelectorAll(".google-map-markers li");if(c.length)for(var l=[],s=0;s<c.length;s++){var m=c[s];getLatLngObject(m.getAttribute("data-location"),m,$(".google-map-container")[a],function(e,t,o){var a=t.getAttribute("data-icon")||o.getAttribute("data-icon"),n=(t.getAttribute("data-icon-active")||o.getAttribute("data-icon-active"),"<p><strong>"+t.getAttribute("data-title")+"</strong></p>"||""),i="<p>"+t.getAttribute("data-description")+"</p>"||"",r=new google.maps.InfoWindow({content:n+i});t.infoWindow=r;var c={position:e,map:o.map};a&&(c.icon=a);var s=new google.maps.Marker(c);t.gmarker=s,l.push({markerElement:t,infoWindow:r}),s.isActive=!1,google.maps.event.addListener(r,"closeclick",function(e,t){var o=null;e.gmarker.isActive=!1,o=e.getAttribute("data-icon")||t.getAttribute("data-icon"),e.gmarker.setIcon(o)}.bind(this,t,o)),google.maps.event.addListener(s,"click",function(e,t){if(0!==e.infoWindow.getContent().length){for(var o,a,n=e.gmarker,i=0;i<l.length;i++){var r;l[i].markerElement===e&&(a=l[i].infoWindow),(o=l[i].markerElement.gmarker).isActive&&l[i].markerElement!==e&&(o.isActive=!1,r=l[i].markerElement.getAttribute("data-icon")||t.getAttribute("data-icon"),o.setIcon(r),l[i].infoWindow.close())}n.isActive=!n.isActive,n.isActive?((r=e.getAttribute("data-icon-active")||t.getAttribute("data-icon-active"))&&n.setIcon(r),a.open(g,s)):((r=e.getAttribute("data-icon")||t.getAttribute("data-icon"))&&n.setIcon(r),a.close())}}.bind(this,t,o))})}}})}function getLatLngObject(e,t,o,a){var n={};try{n=JSON.parse(e),a(new google.maps.LatLng(n.lat,n.lng),t,o)}catch(n){o.geocoder.geocode({address:e},function(e,n){if(n===google.maps.GeocoderStatus.OK){var i=e[0].geometry.location.lat(),r=e[0].geometry.location.lng();a(new google.maps.LatLng(parseFloat(i),parseFloat(r)),t,o)}})}}$(".google-map-container").length&&initMaps.call();</script>';

        }
        return $marker_css.$gmap.$gscript;
    }

    public function getAdminUser(){
        $session=new Session();
        $user_id=$session->get('admin');
        $user=$this->getEntityManager()->findByOne(Users::class, ['id'=>$user_id]);
        return $user;
    }

    public function getUser(){
        $session=new Session();
        $user_id=$session->get('id');
        $user=$this->getEntityManager()->findByOne(Users::class, ['id'=>$user_id]);
        return $user;
    }

    public function getTeam(){
        $team=$this->getEntityManager()->findAll(Team::class);
        return !is_object($team)?$team:[$team];
    }

    public function getPartner(){
        $partner=$this->getEntityManager()->findAll(Partner::class);
        return is_object($partner)?[$partner]:$partner;
    }

    public function getNext($page_type, $post_id, $lang='de'){
        return $this->getEntityManager()->executeQuery("SELECT * FROM `page` WHERE `page_type`='".$page_type."' and `id`>".$post_id." and `status`='publish' limit 0, 1", Page::class);
    }
    
    public function getPrev($page_type, $post_id, $lang='de'){
        return $this->getEntityManager()->executeQuery("SELECT * FROM `page` WHERE `page_type`='".$page_type."' and `id`<".$post_id." and `status`='publish' ORDER BY `id` DESC limit 0, 1", Page::class);
    }

    public function getTopTags(){
        $posts=$this->getEntityManager()->find(Page::class, ['page_type'=>'post']);
        $tags_liste=[];
        foreach($posts as $post){
            foreach(json_decode($post->__get('keywords'), true) as $tags){
                $tags_liste[$tags['tag']]=array_key_exists($tags['tag'], $tags_liste)?$tags_liste[$tags['tag']]+1:1;
            }
        }
        arsort($tags_liste);
        return $tags_liste;
    }

    public function getPages($page_type="page", $count=null, $sort="ASC"){
        if ($count===null){
            $pages=$this->getEntityManager()->find(Page::class, ['page_type'=>$page_type], null, ['id'=>$sort]);
        }
        else {
            $pages=$this->getEntityManager()->find(Page::class, ['page_type'=>$page_type], null, ['id'=>$sort], [0, $count]);
        }
        return is_object($pages)&&$pages!==false?[$pages]:$pages;
    }

    public function getPageType(){
        $page_type=$this->getEntityManager()->find(Page_Type::class, null, null, ['id'=>'ASC']);
        return is_object($page_type)&&$page_type!==false?[$page_type]:$page_type;
    }

    public function getPageTypeTitle($name){
        if ($name!=='page'){
            $page_type=$this->getEntityManager()->findByOne(Page_Type::class, ['name'=>$name]);
            return $page_type!==false?$page_type->__get('title'):'Seitentyp nicht vorhanden';
        }
        else return 'Standart';
        
    }

    public function getActiveMenu(){
        $router=new Router();
        return $router->getRouter()['page_name'];
    }

    public function getTemplateLayout($array=false){
        $theme=$this->getTemplateName();
        $theme_info=json_decode(file_get_contents(ROOT_DIR."/public/template/".$theme."/theme.json"), $array);
        if (!$array){
            if (property_exists($theme_info->options, 'layout')){
                return $theme_info->options->layout;
            }
            else return false;
        }
        else if ($array){
            if (isset($theme_info['options']['layout'])){
                return $theme_info['options']['layout'];
            }
            else return false;
        }
        
    }
    
}