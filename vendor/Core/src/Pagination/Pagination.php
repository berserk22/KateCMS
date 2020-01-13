<?php

namespace Core\Pagination;

use Core\MySQL\PDOMySQL;
use Core\Http\Request;
use Core\Helper\stdObject;
use Core\Entity\Setting;

class Pagination {
    
    protected $em;

    protected $posts_count;

    protected $posts_per_page=10;

    protected $page_num;

    protected $max_page_num;

    protected $db_limit;

    protected $db_limit_array;

    protected $page_liste;
    
    public function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }
    
    public function __construct() {
        $request=new Request();

        $set_posts_per_page=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'posts_per_page'])->__get('value');
        $this->set_posts_per_page($set_posts_per_page);

        $page_num=isset($request->getQuery()['page'])?(int)$request->getQuery()['page']:1;
        $this->set_page_num($page_num);
    }

    public function set_posts_count(int $posts_count){
        $this->posts_count=$posts_count;
        $this->set_max_page_num();
        $this->set_db_limit();
    }

    public function set_posts_per_page(int $posts_per_page) {
        $this->posts_per_page=$posts_per_page;
    }

    public function get_posts_count(){
        return $this->posts_count;
    }

    public function get_posts_per_page(){
        return $this->posts_per_page;
    }

    public function set_page_num(int $page_num){
        $this->page_num=$page_num;
    }

    public function get_page_num(){
        return $this->page_num;
    }

    public function set_db_limit(){
        $limit_start=($this->get_page_num()-1)*$this->get_posts_per_page();
        $limit_end=$this->get_posts_per_page();
        $this->db_limit="LIMIT ".$limit_start.",".$limit_end;
        $this->db_limit_array=[$limit_start, $limit_end];
    }

    public function get_db_limit(bool $array=false){
        if (!$array){
            return $this->db_limit;
        }
        else {
            return $this->db_limit_array;
        }
        
    }

    public function get_next_page(){
        if ($this->get_page_num()!==$this->get_max_page_num()){
            return $this->get_page_num()+1;
        }
        else {
            return null;
        }
    }

    public function get_prev_page(){
        if ($this->get_page_num()!==1){
            return $this->get_page_num()-1;
        }
        else {
            return null;
        }
    }

    public function set_max_page_num(){
        $max_page_num=intdiv($this->get_posts_count(), $this->get_posts_per_page());
        $rest=$this->get_posts_count()%$this->get_posts_per_page();
        if ($rest!==0){
            $max_page_num++;
        }
        $this->max_page_num=$max_page_num;
    }

    public function get_max_page_num(){
        if($this->get_posts_count()===null){
            $this->max_page_num=1;
        }
        else {
            $this->set_max_page_num();
        }
        return $this->max_page_num;
    }

    public function get_page_liste(){
        if ($this->get_page_num()<=3){
            for($i=1;$i<=$this->get_page_num(); $i++){
                if ($this->get_page_num()===$i){
                    $this->page_liste[$i]='current';
                }
                else {
                    $this->page_liste[$i]=$i;
                }
            }
        }
        else {
            for($i=$this->get_page_num()-2;$i<=$this->get_page_num(); $i++){
                if ($this->get_page_num()===$i){
                    $this->page_liste[$i]='current';
                }
                else {
                    $this->page_liste[$i]=$i;
                }
            }
        }

        if ($this->get_page_num()+2<$this->get_max_page_num()){
            for($i=$this->get_page_num()+1;$i<=$this->get_page_num()+2; $i++){
                $this->page_liste[$i]=$i;
            }
        }
        else {
            for($i=$this->get_page_num()+1;$i<=$this->get_max_page_num(); $i++){
                $this->page_liste[$i]=$i;
            }
        }

        if (count($this->page_liste)<5){
            $tmp_count=5-count($this->page_liste);
            $max_key=max(array_keys($this->page_liste));
            for($i=$tmp_count; $i<=$max_key+$tmp_count; $i++){
                if (!array_key_exists($i, $this->page_liste) && $this->get_max_page_num()>$i){
                    if (!($i<$this->get_page_num())){
                        $this->page_liste[$i]=$i;
                    }                    
                }
                
            }
        }

        return count($this->page_liste)===1?false:$this->page_liste;
    }

    public function get_query_string(){
        $query_string=str_replace("page=".$this->get_page_num(), "", filter_input(INPUT_SERVER, 'QUERY_STRING'));
        if ($query_string!==''){
            $query_string="?".$query_string;
        }
        else {
            $query_string="?";
        }

        if (substr($query_string, -1)==='&'){
            $query_string=substr_replace($query_string ,"", -1);
        }
        return $query_string;
    }
    
}
