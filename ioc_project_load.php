<?php
/**
 * Load extra libraries and setup class autoloader
 *
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PROJECTS')) define('DOKU_PROJECTS', DOKU_INC.'lib/plugins/wikiiocmodel/projects/');

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
        if ($type_class) {
            $arr_class_dir = getClassDir($type_class);
            if (!$arr_class_dir) return;
        }else {
            return;
        }
        
        $projectDir = "/".trim(DOKU_PROJECTS.$plugin_controller->getCurrentProject(), '/')."/";
        $dokuModelManager = $projectDir."DokuModelManager.php"; //[TODO Rafa] ¿por qué DokuModelManager.php a pelo?
        $existDokuModelManager = @file_exists($dokuModelManager);

        if (is_null($defClasses)) {
            $defClasses = getMainClass($projectDir);
        }
        if ($existDokuModelManager) {
            include_once $dokuModelManager;
            if (is_null($defClasssProj)) {
                $defClasssProj = DokuModelManager::getDefaultMainClass();
            }
        }

        if (isset($defClasses[$name])) {
            if (@file_exists($defClasses[$name]) && is_file($defClasses[$name])) {
                require_once($defClasses[$name]);
                return;
            }elseif (isset($defClasssProj[$name])) {
                if (@file_exists($defClasssProj[$name]) && is_file($defClasssProj[$name])) {
                    require_once($defClasssProj[$name]);
                    return;
                }
            }
        }

        /*
         * La ruta y nombre del fichero que contiene la classe solicitada se compone de:
         * - una ruta base + 
         * - el directorio del proyecto + 
         * - el directorio del tipo de clase (indicado en el archivo de configuración del proyecto) +
         * - el nombre de la clase
         */
        //$type_class = splitCamelCase($name, "last"); ya se ha obtenido en el filtro previo
        if ($type_class) {
            //$arr_class_dir = getClassDir($type_class); ya se ha obtenido en el filtro previo
            foreach ($arr_class_dir as $dir) {
                $class_file = $projectDir.$dir."/".$name.".php";
                //Busca la clase en las rutas propias del proyecto
                if (@file_exists($class_file) && is_file($class_file)) {
                    include_once ($class_file);
                    break;
                }else if($existDokuModelManager) {
                    //Si no encuentra la clase solicitada en las rutas propias del proyecto, buscará rutas alternativas definidas en DokumodelManager
                    $arr_project_class_dir = DokuModelManager::getDefaultClassDir($type_class);
                    foreach ($arr_project_class_dir as $projdir) {
                        $fichero = $projdir."/".$name.".php";
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

/* Establece las rutas propias ($projectdir) de las clases del proyecto actual */
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

/* Establece las clases, a cargar por defecto, para cualquier proyecto */
function getMainClass($projectDir) {
    $defClasses = array(
                    "DokuModelAdapter" => "${projectDir}DokuModelAdapter.php"
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
