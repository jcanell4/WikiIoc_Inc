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
    static $classes = null;
    static $defaultClasses = null;

    /*
     * Carga la clase solicitada del conjunto de clases principales del proyecto.
     * Si no existe la clase solicitada específica en el proyecto,
     * la busca en el proyecto por defecto
     */
    if ($plugin_controller) {
        $projectDir = DOKU_PROJECTS.$plugin_controller->getCurrentProject();

        if (is_null($classes)) {
            $classes = getMainClass($projectDir);
        }
        if (is_null($defaultClasses)) {
            $defaultClasses = getDefaultMainClass();
        }

        if (isset($classes[$name])) {
            if (@file_exists($classes[$name]) && is_file($classes[$name])) {
                require_once($classes[$name]);
                return;
            }elseif (isset($defaultClasses[$name])) {
                if (@file_exists($defaultClasses[$name]) && is_file($defaultClasses[$name])) {
                    require_once($defaultClasses[$name]);
                    return;
                }
            }
        }
    }
    
    if ($plugin_controller) {
        //$projectDir = DOKU_PROJECTS.$plugin_controller->getCurrentProject();
        $defaultClassCfg = $projectDir."projectClassCfg.php";
        $existsDefaultClassCfg = @file_exists($defaultClassCfg);
        /*
         * La ruta y nombre del fichero que contiene la classe solicitada se compone de:
         * - una ruta base + 
         * - el directorio del proyecto + 
         * - el directorio del tipo de clase (indicado en el archivo de configuración del proyecto) +
         * - el nombre de la clase
         */
        $type_class = splitCamelCase($name, "last");
        if ($type_class) {
            $class_dir = getClassDir($type_class);
            foreach ($class_dir as $dir) {
                $class_file = $projectDir."/".$dir."/".$name.".php";
                if ($class_file && @file_exists($class_file) && is_file($class_file)) {
                    include_once ($class_file);
                    break;
                }else if($existsDefaultClassCfg) {
                    include_once $defaultClassCfg;
                    $arr_default_class_dir = projectClassCfg::getDefaultClassDir($type_class);
                    foreach ($arr_default_class_dir as $dir) {
                        $fichero = $dir."/".$name.".php";
                        if (@file_exists($fichero) && is_file($fichero)) {
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

function getMainClass($projectDir) {
    $classes = array(
                  "DokuModelAdapter" => "${projectDir}DokuModelAdapter.php"
                 ,"DokuModelManager" => "${projectDir}DokuModelManager.php"
               );
    return $classes;
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
