<?php
/**
 * Load extra libraries and setup class autoloader
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(realpath(dirname(__FILE__) . '/../../')) . '/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_NAME_REGEX')) define('DOKU_PLUGIN_NAME_REGEX', '[a-zA-Z0-9\x7f-\xff]+');

spl_autoload_register('ioc_autoload');

/**
 * spl_autoload_register callback
 *
 * Cuando una clase, perteneciente a un proyecto concreto de un plugin, es instanciada,
 * se carga el fichero php que contiene dicha clase.
 */
function ioc_autoload($name) {
    global $plugin_controller;
    static $classes = null;
    if (is_null($classes)) {
        $classes = array(
            'Ioc_Plugin_Controller'  => DOKU_INC.'inc/inc_ioc/ioc_plugincontroller.php',

            'DokuWiki_Action_Plugin' => DOKU_PLUGIN.'action.php',
            'DokuWiki_Admin_Plugin'  => DOKU_PLUGIN.'admin.php',
            'DokuWiki_Syntax_Plugin' => DOKU_PLUGIN.'syntax.php',
            'DokuWiki_Remote_Plugin' => DOKU_PLUGIN.'remote.php',
            'DokuWiki_Auth_Plugin'   => DOKU_PLUGIN.'auth.php',
            
            'MetaDataService'        => DOKU_PLUGIN.'wikiiocmodel/metadata/MetaDataService.php',
            
            'WikiGlobalConfig'        => DOKU_PLUGIN.'owninit/WikiGlobalConfig.php',
            'WikiIocLangManager'        => DOKU_PLUGIN.'wikiiocmodel/WikiIocLangManager.php',
            'WikiIocInfoManager'        => DOKU_PLUGIN.'wikiiocmodel/WikiIocInfoManager.php'
        );
    }

    if (isset($classes[$name])) {
        require_once($classes[$name]);
        return;
    }

    /*
     * El nombre de la clase buscada debe ser: 
     * - si la clase está en un fichero llamado <tipo>.php: 
     *      <tipo>_plugin_<nombre_del_plugin>_projects_<nombre_del_proyecto>
     * - si la clase está en un fichero dentro del directorio <tipo>: 
     *      <tipo>_plugin_<nombre_del_plugin>_projects_<nombre_del_proyecto>_<nombre_del_fichero_php>
     */
    if (preg_match('/^(auth|command|helper|syntax|action|admin|renderer|remote)_plugin_('
                    .DOKU_PLUGIN_NAME_REGEX.')_projects_('.DOKU_PLUGIN_NAME_REGEX.')(?:_([^_]+))?$/',
                    $name, $m)) {
        // try to load the wanted class file
        if (count($m) >= 4 && $plugin_controller->getCurrentProject() !== $m[3]) {
            echo 'el nom del projecte no coincideix';
        }else {
            // [TODO Rafael] no me gusta establecer el nombre de un plugin particular
            if ($m[2]=='wikiiocmodel') {
                $c = "/{$m[4]}";
            }
            $c = ((count($m) === 5) ? "/{$m[4]}" : '');
            $plg = DOKU_PLUGIN . "{$m[2]}/projects/{$m[3]}/{$m[1]}$c.php";
            if (@file_exists($plg)) include_once $plg;
            return;
        }
    }
    // El nombre de la clase buscada debe ser: command_plugin_<nombre_del_plugin>
    elseif(preg_match('/^(command)_plugin_('.DOKU_PLUGIN_NAME_REGEX.')(?:_([^_]+))?$/', $name, $m)) {
        // try to load the wanted class file
        $c = ((count($m) === 4) ? "/{$m[3]}" : '');
        $plg = DOKU_PLUGIN . "{$m[2]}/{$m[1]}$c.php";
        if(@file_exists($plg)){
            include_once DOKU_PLUGIN . "{$m[2]}/{$m[1]}$c.php";
        }
        return;
    }
}
