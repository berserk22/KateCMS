<?php

use Core\Log\Logger;

class Loader {

    protected $prefixes = array();

    public function register() {
        spl_autoload_register(array($this, 'loadClass'));
    }

    public function addNamespace($prefix, $base_dir, $prepend = false) {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }
    
    public function loadClass($class) {
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);
            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }
            $prefix = rtrim($prefix, '\\');
        }
        return false;
    }
    
    protected function loadMappedFile($prefix, $relative_class) {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }
        foreach ($this->prefixes[$prefix] as $base_dir) {
            $file = $base_dir
                  . str_replace('\\', '/', $relative_class)
                  . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        return false;
    }
    
    protected function requireFile($file) {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }

}

function ErrorHandler ($errno, $errmsg, $filename, $linenum, $vars) {
    $dt = date("d.m.Y H:i:s");
    $errortype = array (
        1     =>  "ERROR",
        2     =>  "WARNING",
        4     =>  "PARSE",
        8     =>  "NOTICE",
        16    =>  "CORE_ERROR",
        32    =>  "CORE_WARNING",
        64    =>  "COMPILE_ERROR",
        128   =>  "COMPILE_WARNING",
        256   =>  "USER_ERROR",
        512   =>  "USER_WARNING",
        1024  =>  "USER_NOTICE",
        2048  =>  "E_STRICT",
        4096  =>  "E_RECOVERABLE_ERROR",
        8192  =>  "E_DEPRECATED",
        16384 =>  "E_USER_DEPRECATED",
        32767 =>  "E_ALL",
    );
    if ($errno!==8){
        $err  = "[ ".$dt." ] ".$errortype[$errno]." : Linie [ ".$linenum." ] : \"";
        $err .= $errmsg."\" ";
        $err .= "-> ".$filename."\n";
        Logger::ErrorReport($err);
    }
}

function ShutdownHandler() {
    if (@is_array($e = @error_get_last())) {
        $code = isset($e['type']) ? $e['type'] : 0;
        $msg = isset($e['message']) ? $e['message'] : '';
        $file = isset($e['file']) ? $e['file'] : '';
        $line = isset($e['line']) ? $e['line'] : '';
        if($code>0)ErrorHandler($code,$msg,$file,$line,'');
    }
}