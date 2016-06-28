<?php
/**
 * Load extra libraries and setup class autoloader
 *
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PROJECTS')) define('DOKU_PROJECTS', DOKU_PLUGIN.'wikiiocmodel/projects/');
if (!defined('IOC_CLASS_NAME_REGEX')) define('IOC_CLASS_NAME_REGEX', '[a-zA-Z0-9\x7f-\xff]+');
spl_autoload_register('ioc_project_autoload');
function ioc_project_autoload($name) {
    global $plugin_controller;
    /*
     * La ruta y nombre del fichero se compone de:
     * - una ruta base + 
     * - el directorio del proyecto + 
     * - el directorio del tipo de clase (indicado en el archivo de configuración del proyecto) +
     * - el nombre de la clase
     */
    if ($plugin_controller)
        $projectDir = DOKU_PROJECTS.$plugin_controller->getCurrentProject();
    
    $projectDir = DOKU_PROJECTS."defaultProject";   //Valor Hard-Coded temporal
    
    include_once $projectDir."/projectClassCfg.php";
    $type_class = splitCamelCase($name, "last");
    if ($type_class) {
        $class_dir = projectClassCfg::getClassDir($type_class);
        if ($class_dir)
            $class_file = $projectDir."/".$class_dir."/".$name.".php";
    
        if ($class_file && file_exists($class_file) && is_file($class_file)) {
            include_once ($class_file);
        }
        else {
            $arr_default_class_dir = projectClassCfg::getDefaultClassDir($type_class);
            foreach ($arr_default_class_dir as $dir) {
                $fichero = $dir."/".$name.".php";
                if (file_exists($fichero) && is_file($fichero)) {
                    include_once ($fichero);
                }
            }
        }
    }
    return;
}
function splitCamelCase($name, $elem) {
    $arr = preg_split("/[A-Z]/", $name);
    foreach ($arr as $v) {
        if ($v && $v != $name) {
            $p = strpos($name, $v)-1;
            $ret[] = substr($name, $p, 1) . $v;
        }
    }
    return ($elem=="last") ? $ret[count($ret)-1]: $ret;
}

//<?php
///**
// * Load extra libraries and setup class autoloader
// *
// * @culpable Rafael Claver
// */
//if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(dirname(__FILE__).'/../../').'/');
//if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
//if (!defined('DOKU_PROJECTS')) define('DOKU_PROJECTS', DOKU_PLUGIN.'wikiiocmodel/projects/');
//if (!defined('IOC_CLASS_NAME_REGEX')) define('IOC_CLASS_NAME_REGEX', '[a-zA-Z0-9\x7f-\xff]+');
//
//spl_autoload_register('ioc_project_autoload');
//
//function ioc_project_autoload($name) {
//    global $plugin_controller;
//    /*
//     * La ruta y nombre del fichero se compone de:
//     * - una ruta base + 
//     * - el directorio del proyecto + 
//     * - el directorio del tipo de clase (indicado en el archivo de configuración del proyecto) +
//     * - el nombre de la clase
//     */
//    if (!$plugin_controller){
//        return;
//    }
//    $projectDir = DOKU_PROJECTS.$plugin_controller->getCurrentProject();
//    
//    //$projectDir = DOKU_PROJECTS."defaultProject";   //Valor Hard-Coded temporal
//    
//    if(!@file_exists($projectDir."/projectClassCfg.php")){
//        return;
//    }
//        
//    include_once $projectDir."/projectClassCfg.php";
//
//    $type_class = splitCamelCase($name, "last");
//    if ($type_class) {
//        $class_dir = projectClassCfg::getClassDir($type_class);
//        if ($class_dir)
//            $class_file = $projectDir."/".$class_dir."/".$name.".php";
//    
//        if ($class_file && file_exists($class_file) && is_file($class_file)) {
//            include_once ($class_file);
//        }
//        else {
//            $arr_default_class_dir = projectClassCfg::getDefaultClassDir($type_class);
//            foreach ($arr_default_class_dir as $dir) {
//                $fichero = $dir."/".$name.".php";
//                if (file_exists($fichero) && is_file($fichero)) {
//                    include_once ($fichero);
//                }
//            }
//        }
//    }
//    return;
//}
//
//function splitCamelCase($name, $elem) {
//    $arr = preg_split("/[A-Z]/", $name);
//    foreach ($arr as $v) {
//        if ($v && $v != $name) {
//            $p = strpos($name, $v)-1;
//            $ret[] = substr($name, $p, 1) . $v;
//        }
//    }
//    return ($elem=="last") ? $ret[count($ret)-1]: $ret;
//}