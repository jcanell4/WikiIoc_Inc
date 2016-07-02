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
    if ($plugin_controller) {
        $projectDir = DOKU_PROJECTS.$plugin_controller->getCurrentProject();
        $defaultClassCfg = $projectDir."/projectClassCfg.php";
        $existsDefaultClassCfg = @file_exists($defaultClassCfg);

        $type_class = splitCamelCase($name, "last");
        if ($type_class) {
            $class_dir = getClassDir($type_class);
            foreach ($class_dir as $dir) {
                $class_file = $projectDir."/".$dir."/".$name.".php";
                if ($class_file && file_exists($class_file) && is_file($class_file)) {
                    include_once ($class_file);
                    break;
                }else if($existsDefaultClassCfg) {
                    include_once $defaultClassCfg;
                    $arr_default_class_dir = projectClassCfg::getDefaultClassDir($type_class);
                    foreach ($arr_default_class_dir as $dir) {
                        $fichero = $dir."/".$name.".php";
                        if (file_exists($fichero) && is_file($fichero)) {
                            include_once ($fichero);
                            break 2;
                        }
                    }
                }
            }
        }
    }
    return;
}

function getClassDir($name) {
   $cfg = array (
            "Action" => array (
                           "actions"
                          ,"actions/extra"
                        )
           ,"Authorization" => array (
                                 "authorization"
                               )
           ,"Model" => array (
                           "datamodel"
                       )
          );
   return $cfg[$name];
}

function splitCamelCase($name, $elem) {
    $ret = null;
    $arr = preg_split("/[A-Z]/", $name);
    foreach ($arr as $v) {
        if ($v && $v != $name) {
            $p = strpos($name, $v)-1;
            $ret[] = substr($name, $p, 1) . $v;
        }
    }
    return ($elem=="last") ? $ret[count($ret)-1]: $ret;
}
