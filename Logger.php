<?php
if(!defined('DOKU_INC')) die();
/**
 * Modo de Uso en un fichero (con valores por defecto estándar):
 *    include_once(DOKU_INC."inc/inc_ioc/Logger.php");
 *    function f() {
 *        Logger::debug($TextoDescriptivo+ValoresVariables, $NúmError=0, __LINE__, __FILE__, $level=-1, $append=TRUE);
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
        $append = ($append===NULL) ? self::$fileAppend : ($append) ? $append=FILE_APPEND : $append;

        if(self::$fileName===NULL){
            $debugFile = DOKU_INC.'lib/plugins/tmp/debug.log';
        }else{
            $debugFile = DOKU_INC.'lib/plugins/tmp/'.self::$fileName;
        }
        msg($message, $err, $line, $file);
        $tag = ($err===0) ? "Info" : "Error($err): ";
        $date = date("d-m-Y H:i:s");
        file_put_contents($debugFile, "$date ($tag)=> $message ($file:$line)\n", $append);
    }
}
