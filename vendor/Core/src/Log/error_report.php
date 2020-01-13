<?php

use Core\Log\Logger;

function ErrorHandler ($errno, $errmsg, $filename, $linenum, $vars) {
    $dt = date("d.m.Y H:i:s");
    $errortype = array (
        1   =>  "ERROR",
        2   =>  "WARNING",
        4   =>  "PARSE",
        8   =>  "NOTICE",
        16  =>  "CORE_ERROR",
        32  =>  "CORE_WARNING",
        64  =>  "COMPILE_ERROR",
        128 =>  "COMPILE_WARNING",
        256 =>  "USER_ERROR",
        512 =>  "USER_WARNING",
        1024=>  "USER_NOTICE"
    );
    $user_errors = array(E_USER_ERROR, E_USER_WARNING);
    if ($errno!==8){
        $err  = "[ ".$dt." ] ".$errortype[$errno]." : Linie [ ".$linenum." ] : \"";
        $err .= $errmsg."\" ";
        $err .= "-> ".$filename."\n";
        Logger::ErrorReport($err."\r\n");
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