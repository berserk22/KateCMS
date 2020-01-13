<?php

namespace Core\Config;

use Core\MySQL\PDOMySQL;
use Core\Entity\Setting;

class SystemConfig {

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

    public function getSetting(string $key=''){
        if ($key!==null){
            return $this->getEntityManager()->findByOne(Setting::class, ['key'=>$key])->__get('value');
        }
        else {
            return null;
        }
    }

}