<?php
/**
 * Load extra libraries and setup class autoloader
 *
 * @culpable Rafael Claver
 */

if (!defined('DOKU_INC')) define('DOKU_INC', fullpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_NAME_REGEX')) define('DOKU_PLUGIN_NAME_REGEX', '[a-zA-Z0-9\x7f-\xff]+');

spl_autoload_register('ioc_autoload');

/**
 * spl_autoload_register callback
 *
 * Contains a static list of DokuWiki's extra classes and automatically
 * require()s their associated php files when an object is instantiated.
 *
 * @culpable Rafael Claver
 */
function ioc_autoload($name) {
    global $plugin_controller;
    static $classes = null;
    if (is_null($classes)) {
        $classes = array(
            'Ioc_Plugin_Controller' => DOKU_INC.'inc/inc_ioc/ioc_plugincontroller.php',
        );
    }

    if (isset($classes[$name])) {
        require_once($classes[$name]);
        return;
    }

    // Plugin loading
    if (preg_match('/^(auth|command|helper|syntax|action|admin|renderer|remote)_plugin_('
                    .DOKU_PLUGIN_NAME_REGEX.')_projects_('.DOKU_PLUGIN_NAME_REGEX.')(?:_([^_]+))?$/',
                    $name, $m)) {
        // try to load the wanted plugin file
        if (count($m) >= 4 && $plugin_controller->getCurrentProject() !== $m[3]) {
            echo 'el nom del projecte no coincideix';
        }else {
            if ($m[2]=='wikiiocmodel') {
                $c = "/{$m[4]}";
            }
            $c = ((count($m) === 5) ? "/{$m[4]}" : '');
            $plg = DOKU_PLUGIN . "{$m[2]}/projects/{$m[3]}/{$m[1]}$c.php";
            if (@file_exists($plg)) include_once $plg;
            return;
        }
    }
    elseif(preg_match('/^(command)_plugin_('.DOKU_PLUGIN_NAME_REGEX.')(?:_([^_]+))?$/',
                    $name, $m)) {
        // try to load the wanted plugin file
        $c = ((count($m) === 4) ? "/{$m[3]}" : '');
        $plg = DOKU_PLUGIN . "{$m[2]}/{$m[1]}$c.php";
        if(@file_exists($plg)){
            include_once DOKU_PLUGIN . "{$m[2]}/{$m[1]}$c.php";
        }
        return;
    }
}
