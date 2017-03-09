<?php
/**
 * Load extra libraries from projects and setup class autoloader
 *
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(realpath(dirname(__FILE__) . '/../../')) . '/');
define('WIKI_IOC_MODEL', DOKU_INC . 'lib/plugins/wikiiocmodel/');
define('WIKI_IOC_PROJECTS', WIKI_IOC_MODEL . 'projects/');
define('DOKUMODELMANAGER', 'DokuModelManager.php');

spl_autoload_register('ioc_project_autoload');

function ioc_project_autoload($name) {
    global $plugin_controller;
    static $defClasses = null;
    static $defClasssProj = null;

    /*
     * Carga, si existen, las clases por defecto definidas en el proyecto.
     */
    if ($plugin_controller) {
        //Filtro previo
        $type_class = splitCamelCase($name, "last");
        if ($type_class) $arr_dir_class = getDirClass($type_class);
        if (!$type_class || !$arr_dir_class) return;

        $projectDir = "/".trim(WIKI_IOC_PROJECTS.$plugin_controller->getCurrentProject(), '/')."/";
        $dokuModelManager = $projectDir.DOKUMODELMANAGER;
        $existDokuModelManager = @file_exists($dokuModelManager);

        // En el DokuModelManager de cada proyecto se establecen las rutas a las clases que necesita el proyecto
        if ($existDokuModelManager) {
            include_once $dokuModelManager;
            if (is_null($defClasssProj)) {
                $defClasssProj = DokuModelManager::getDefaultMainClass();
            }
        }
        if (is_null($defClasses)) {
            $defClasses = getMainClass($projectDir);
        }

        //Aquí se averigua (y, en su caso, se carga) si se ha solicitado una Clase principal
        if (isset($defClasssProj[$name]) &&
            @file_exists($defClasssProj[$name]) &&
            is_file($defClasssProj[$name])) {
                require_once($defClasssProj[$name]);
                return;
        }elseif (isset($defClasses[$name]) &&
            @file_exists($defClasses[$name]) &&
            is_file($defClasses[$name])) {
                require_once($defClasses[$name]);
                return;
        }

        /*
         * La ruta y nombre del fichero que contiene la classe solicitada se compone de:
         * - una ruta base +
         * - el directorio del proyecto +
         * - el directorio del tipo de clase (indicado en el archivo de configuración del proyecto) +
         * - el nombre de la clase
         */
        foreach ($arr_dir_class as $dir) {
            $class_file = $projectDir.$dir.$name.".php";
            //Busca la clase en las rutas propias del proyecto
            if (@file_exists($class_file) && is_file($class_file)) {
                include_once ($class_file);
                return;
            }
        }
        //Si no encuentra la clase solicitada en las rutas propias del proyecto, buscará en rutas alternativas definidas en DokumodelManager
        if ($existDokuModelManager) {
            $arr_project_dir_class = DokuModelManager::getDefaultDirClass($type_class);
            foreach ($arr_project_dir_class as $projdir) {
                $fichero = $projdir.$name.".php";
                if (@file_exists($fichero) && is_file($fichero)) {
                    include_once ($fichero);
                    return;
                }
            }
        }
        //Si todavía no la encuentra, buscará la clase en las rutas de la raíz WIKI_IOC_MODEL
        foreach ($arr_dir_class as $dir) {
            $class_file = WIKI_IOC_MODEL.$dir.$name.".php";
            if (@file_exists($class_file) && is_file($class_file)) {
                include_once ($class_file);
                return;
            }
        }
    }
    return;
}

/* Establece las rutas propias ($projectdir) de las clases del proyecto actual */
function getDirClass($name) {
   $cfg = array (
            "Action" => array (
                           "actions/"
                          ,"actions/extra/"
                        )
           ,"Authorization" => array (
                                 "authorization/"
                               )
           ,"Model" => array (
                           "datamodel/"
                       )
          );
   return $cfg[$name];
}

/* Establece las clases, a cargar por defecto, para cualquier proyecto */
function getMainClass($projectDir) {
    $defClasses = array(
                    "DokuModelAdapter" => "${projectDir}DokuModelAdapter.php",
                    "FactoryAuthorization" => "${projectDir}authorization/FactoryAuthorization.php"
                  );
    return $defClasses;
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
