<?php
if(!defined('DOKU_INC')) die();
/**
 * Description of Logger
 *
 * @author josep
 */
class Logger {
    private static $fileAppend=FALSE;
    private static $fileName;
    private static $debugLevel=0;
    
    public static function init($debugLevel=0, $filename=NULL, $fileAppend=FALSE) {
        self::$debugLevel=$debugLevel;
        self::$fileName = $filename;
        self::$fileAppend = $fileAppend;
    }

    public static function debug($message, $err, $line, $file, $level=1, $append=NULL) {
        if (self::$debugLevel < $level) return;
        if($append===NULL){
            $append = self::$fileAppend;
        }
        if(self::$fileName===NULL){
            $debugFile = DOKU_INC.'lib/plugins/tmp/debug.log';
        }else{
            $debugFile = self::$fileName;
        }
        msg($message, $err, $line, $file);
        $tag = $err===0?"Info: ":"Error($err): ";
        $date = date("d-m-Y H:i:s");
        file_put_contents($debugFile, "$date ($tag)=> $message ($file:$line)\n", $append);
    }    
}
