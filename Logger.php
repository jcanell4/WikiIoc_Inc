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
    private static $fileAppend=TRUE;
    private static $fileName;
    private static $debugLevel=0;

    //marjose comments: 
    //Funcions per guardarles dades de la crida en un fitxer de fàcil accès. Es guarda
    //del moment en el que està el codi. 
    //fileAppend a false, cada vegada que fa un debug comença de nou. 
    //debugLevel: indica el nivell amb el que vols iniciar, nivell 0 indica superior o igual al nivell 0. marca el minim
    //fas el init en el moment de fer el click
    public static function init($debugLevel=0, $filename=NULL, $fileAppend=TRUE) {
        self::$debugLevel=$debugLevel;
        self::$fileName = $filename;
        self::$fileAppend = $fileAppend;
    }

    //level: indica el nivell amb el que vols iniciar, nivell 0 indica superior al nivell 0
    //això permet tenir el debug sense comentar i només entraria quan el nivell d'error es superior
    public static function debug($message, $err, $line, $file, $level=1, $append=NULL) {
        if (self::$debugLevel < $level) return;
        
        if($append==NULL){
            $append = self::$fileAppend;
        }
        
        if($append === TRUE){
            $append = FILE_APPEND;
        }else{
            $append = 0;
        }
        //marjose: definim on es guardaran les dades que es capturen al debug
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
