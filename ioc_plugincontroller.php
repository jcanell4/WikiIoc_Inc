<?php
/**
 * Class to extend the encapsulate access to dokuwiki plugins
 *
 * @culpable Rafael Claver
 */
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
require_once(DOKU_INC.'inc/plugincontroller.class.php');
class Ioc_Plugin_Controller extends Doku_Plugin_Controller {
    
    protected $list_byProjectType = array();
    protected $tmp_projects = array();
    protected $project_cascade = array('default'=>array(), 'local'=>array(), 'protected'=>array());
    protected $last_local_config_file_project = '';
    protected $currentProject = '';
    
    /**
     * Populates the parent master list of plugins and add projects
     */
    public function __construct() {
        parent::__construct();
        $this->_populateMasterListProjects();
    }
    public function getList($type='', $all=false) {
        $parenListByType = parent::getList($type, $all);    // request the complete plugin list
        if (!$type) return $parenListByType;
        if (!isset($this->list_byProjectType[$type]['enabled'])) 
            $this->list_byProjectType[$type]['enabled'] = $this->_getListByProjectType($type,true);
        if ($all && !isset($this->list_byProjectType[$type]['disabled'])) 
            $this->list_byProjectType[$type]['disabled'] = $this->_getListByProjectType($type,false);
        return $all ? array_merge($parenListByType,$this->list_byProjectType[$type]['enabled'],$this->list_byProjectType[$type]['disabled']) : array_merge($parenListByType,$this->list_byProjectType[$type]['enabled']);
    }
    public function setCurrentProject($name) {
        $this->currentProject = $name;
    }
    
    public function getCurrentProject() {
        return $this->currentProject;
    }
    
    /**
     * Returns a list of available plugin components of given type
     *
     * @param string $type plugin_type name. Type of plugin to return.
     * @param bool   $enabled   true to return enabled plugins,
     *                          false to return disabled plugins.
     * @return array of plugin components of requested type
     */
    protected function _getListByProjectType($type, $enabled) {
        $master_list = $enabled ? array_keys(array_filter($this->tmp_projects)) : array_keys(array_filter($this->tmp_projects,array($this,'negate')));
        $plugins = array();
        foreach ($master_list as $plugin) {
            $dir = $this->_get_directory_project($plugin);
            if (@file_exists(DOKU_PLUGIN."$dir/$type.php")){
                $plugins[] = $this->_nameTransform($dir);
            } else {
                if ($dp = @opendir(DOKU_PLUGIN."$dir/$type/")) {
                    while (false !== ($component = readdir($dp))) {
                        if (substr($component,0,1) == '.' || strtolower(substr($component, -4)) != ".php") continue;
                        if (is_file(DOKU_PLUGIN."$dir/$type/$component")) {
                            $plugins[] = $this->_nameTransform($dir).'_'.substr($component, 0, -4);
                        }
                    }
                    closedir($dp);
                }
            }
        }
        return $plugins;
    }
    
    /**
     * @param string $plugin name of plugin
     * @return string directory name of plugin
     */
    private function _get_directory_project($plugin) {
        return "$plugin/projects/{$this->getCurrentProject()}";
    }
    
    private function _nameTransform($name) {
        //return str_replace('/', '_', str_replace('/projects/', '~', $name));
        return str_replace('/', '_', $name);
    }
    
    private function _populateMasterListProjects() {
        if ($dh = @opendir(DOKU_PLUGIN)) {
            $all_plugin_projects = array();
            while (false !== ($project = readdir($dh))) {
                if ($project[0] == '.' || is_file(DOKU_PLUGIN.$project)) continue; // skip hidden entries and files, we're only interested in directories
                if (@file_exists(DOKU_PLUGIN.$project.'/projects')) 
                    $all_plugin_projects[$project] = 1;
            }
            $this->tmp_projects = $all_plugin_projects;
        }
    }
    
//if (!defined('DOKU_PROJECT')) define('DOKU_PROJECT', DOKU_INC.'lib/projects/');
    /**
     * Split name in a plugin name and a component name.
     * If '~' exists, it's indicate a project name
     * @param string $name
     * @return array with
     *              - plugin name
     *              - and component name when available, otherwise empty string
     */
//    protected function _splitName($name) {
//        if (array_search($name, array_keys($this->tmp_plugins)) === FALSE) {
//            if (strpos($name, '~') !== FALSE)
//                $name = preg_replace('/~[a-zA-Z0-9]+/', '', $name);
//            return explode('_', $name, 2);
//        }
//        return array($name, '');
//    }
    
//    protected function _populateMasterListProjects() {
//        global $conf;
//
//        if ($dh = @opendir(DOKU_PROJECT)) {
//            $all_projects = array();
//            while (false !== ($project = readdir($dh))) {
//                if ($project[0] == '.' || is_file(DOKU_PROJECT.$project)) continue; // skip hidden entries and files, we're only interested in directories
//                $all_projects[$project] = 1;
//            }
//            $this->tmp_projects = $all_plugins;
//            if (!file_exists($this->last_local_config_file_project)) {
//                $this->saveList(true);
//            }
//        }
//    }
//    
//    protected function loadConfigProjects() {
//        global $config_cascade;
//        foreach(array('default','protected') as $type) {
//            if(array_key_exists($type,$config_cascade['projects']))
//                $this->project_cascade[$type] = $this->checkRequire($config_cascade['projects'][$type]);
//        }
//        $local = $config_cascade['projects']['local'];
//        $this->last_local_config_file_project = array_pop($local);
//        $this->project_cascade['local'] = $this->checkRequire(array($this->last_local_config_file_project));
//        if(is_array($local)) {
//            $this->project_cascade['default'] = array_merge($this->project_cascade['default'],$this->checkRequire($local));
//        }
//        $this->tmp_projects = array_merge($this->project_cascade['default'], $this->project_cascade['local'], $this->project_cascade['protected']);
//    }
}
