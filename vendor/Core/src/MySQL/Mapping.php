<?php

namespace Core\MySQL;

/**
 * Description of Mapping
 *
 * @author Sergey Tevs
 */

class Mapping {
    
    public function __construct($entity) {
        $entity=ltrim($entity, '\\');
        $fileName='';
        $namespace='';    
        if ($lastNsPos=strrpos($entity, '\\')) {
            $namespace=substr($entity, 0, $lastNsPos);
            $entity=substr($entity, $lastNsPos + 1);
            $fileName=str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
        }
        $fileName.= $entity.'.php';            
        
        $file=ROOT_DIR.'/'.$fileName;
        $content = file_get_contents($file);
        
//        $content=explode('/**', $content);
        
        var_dump($content);
        
    }
    
}
