<?php
/**
 * Load extra libraries and setup class autoloader
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . '/');
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC.'lib/lib_ioc/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_NAME_REGEX')) define('DOKU_PLUGIN_NAME_REGEX', '[a-zA-Z0-9\x7f-\xff]+');

spl_autoload_register('ioc_autoload');

/**
 * spl_autoload_register callback
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

            'IocCommon'                     => DOKU_LIB_IOC.'common/IocCommon.php',
            'ajaxCall'                      => DOKU_LIB_IOC.'ajaxcommand/ajaxClasses.php',
            'ajaxRest'                      => DOKU_LIB_IOC.'ajaxcommand/ajaxClasses.php',
            'AbstractResponseHandler'       => DOKU_LIB_IOC.'ajaxcommand/AbstractResponseHandler.php',
            'AjaxCmdResponseGenerator'      => DOKU_LIB_IOC.'ajaxcommand/AjaxCmdResponseGenerator.php',
            'abstract_command_class'        => DOKU_LIB_IOC.'ajaxcommand/abstract_command_class.php',
            'abstract_writer_command_class' => DOKU_LIB_IOC.'ajaxcommand/abstract_writer_command_class.php',
            'abstract_project_command_class'=> DOKU_LIB_IOC.'ajaxcommand/abstract_project_command_class.php',
            'abstract_rest_command_class'   => DOKU_LIB_IOC.'ajaxcommand/abstract_rest_command_class.php',

            'JsonGenerator'     => DOKU_LIB_IOC.'ajaxcommand/JsonGenerator.php',
            'JSonGeneratorImpl' => DOKU_LIB_IOC.'ajaxcommand/JsonGenerator.php',
            'ArrayJSonGenerator'=> DOKU_LIB_IOC.'ajaxcommand/JsonGenerator.php',
            'JSonJustEncoded'   => DOKU_LIB_IOC.'ajaxcommand/JsonGenerator.php',

            'WikiGlobalConfig'  => DOKU_PLUGIN.'ownInit/WikiGlobalConfig.php',
            'MetaDataService'   => DOKU_PLUGIN.'wikiiocmodel/metadata/MetaDataService.php',
            'BasicPermission'   => DOKU_PLUGIN.'wikiiocmodel/authorization/BasicPermission.php',
            'ProjectPermission' => DOKU_PLUGIN.'wikiiocmodel/authorization/ProjectPermission.php',

            'UpgradeManager' => DOKU_LIB_IOC.'upgrader/UpgradeManager.php',

            'AbstractActionManager'        => DOKU_LIB_IOC.'wikiiocmodel/AbstractActionManager.php',
            'AbstractCommandAuthorization' => DOKU_LIB_IOC.'wikiiocmodel/AbstractCommandAuthorization.php',
            'AbstractPermission'           => DOKU_LIB_IOC.'wikiiocmodel/AbstractPermission.php',
            'AuthorizationKeys'            => DOKU_LIB_IOC.'wikiiocmodel/AuthorizationKeys.php',

            'AbstractModelManager'   => DOKU_LIB_IOC.'wikiiocmodel/AbstractModelManager.php',
            'WikiIocInfoManager'     => DOKU_LIB_IOC.'wikiiocmodel/WikiIocInfoManager.php',
            'WikiIocLangManager'     => DOKU_LIB_IOC.'wikiiocmodel/WikiIocLangManager.php',
            'WikiIocModelException'  => DOKU_LIB_IOC.'wikiiocmodel/WikiIocModelExceptions.php',
            'WikiIocModelManager'    => DOKU_LIB_IOC.'wikiiocmodel/WikiIocModelManager.php',
            'WikiIocPluginController'=> DOKU_LIB_IOC.'wikiiocmodel/WikiIocPluginController.php',

            'ResourceLocker'            => DOKU_LIB_IOC.'wikiiocmodel/ResourceLocker.php',
            'ResourceLockerInterface'   => DOKU_LIB_IOC.'wikiiocmodel/ResourceLockerInterface.php',
            'ResourceUnlockerInterface' => DOKU_LIB_IOC.'wikiiocmodel/ResourceUnlockerInterface.php',

            'AbstractProjectModel'   => DOKU_LIB_IOC.'wikiiocmodel/datamodel/AbstractProjectModel.php',

            'AdminKeys'            => DOKU_PLUGIN.'ajaxcommand/defkeys/AdminKeys.php',
            'AjaxKeys'             => DOKU_PLUGIN.'ajaxcommand/defkeys/AjaxKeys.php',
            'GlobalKeys'           => DOKU_PLUGIN.'ajaxcommand/defkeys/GlobalKeys.php',
            'LockKeys'             => DOKU_PLUGIN.'ajaxcommand/defkeys/LockKeys.php',
            'MediaKeys'            => DOKU_PLUGIN.'ajaxcommand/defkeys/MediaKeys.php',
            'PageKeys'             => DOKU_PLUGIN.'ajaxcommand/defkeys/PageKeys.php',
            'ProjectKeys'          => DOKU_PLUGIN.'ajaxcommand/defkeys/ProjectKeys.php',
            'RequestParameterKeys' => DOKU_PLUGIN.'ajaxcommand/defkeys/RequestParameterKeys.php',
            'ResponseHandlerKeys'  => DOKU_PLUGIN.'ajaxcommand/defkeys/ResponseHandlerKeys.php',
            'UserStateKeys'        => DOKU_PLUGIN.'ajaxcommand/defkeys/UserStateKeys.php',

            'Logger'               => DOKU_INC.'inc/inc_ioc/Logger.php',
        );
    }

    if (isset($classes[$name])) {
        require_once($classes[$name]);
        return;
    }

    if (preg_match('/.*Exception$/', $name)) {
        require_once(DOKU_LIB_IOC.'wikiiocmodel/WikiIocModelExceptions.php');
        require_once(DOKU_LIB_IOC.'wikiiocmodel/DefaultProjectModelExceptions.php');
        return;
    }

    if (preg_match('/.*Translator$/', $name)) {
        require_once(DOKU_LIB_IOC.'translators/translators.php');
        return;
    }

    if (preg_match('/.*ProjectUpdateProcessor$/', $name)) {
        require_once(DOKU_LIB_IOC.'wikiiocmodel/ProjectUpdateProcessor.php');
        return;
    }

    if (preg_match('/^Validat.*$/', $name)) {
        require_once(DOKU_LIB_IOC.'common/utility/'.$name.'.php');
        return;
    }

    if (preg_match('/Wioccl.*$/', $name)) {
        require_once(DOKU_LIB_IOC.'wioccl/'.$name.'.php');
        return;
    }

    if (preg_match('/_Wioccl.*$/', $name)) {
        require_once(DOKU_LIB_IOC.'wioccl/'.$name.'.php');
        return;
    }

    /*
     * El nombre de la clase buscada debe ser:
     * - si la clase está en un fichero llamado <tipo>.php:
     *      <tipo>_plugin_<nombre_del_plugin>_projects_<nombre_del_proyecto>
     * - si la clase está en un fichero dentro del directorio <tipo>:
     *      <tipo>_plugin_<nombre_del_plugin>_projects_<nombre_del_proyecto>_<nombre_del_fichero_php>
     */
    if (preg_match('/^(auth|command|helper|syntax|action|admin|renderer|remote)_plugin_'.
                     '('.DOKU_PLUGIN_NAME_REGEX.')_projects_('.DOKU_PLUGIN_NAME_REGEX.')(?:_([^_]+))?$/',
                    $name, $m)) {
        // try to load the wanted class file
        if (count($m) >= 4 && $plugin_controller->getCurrentProject() !== $m[3]) {
            echo 'el nom del projecte no coincideix';
        }else {
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
