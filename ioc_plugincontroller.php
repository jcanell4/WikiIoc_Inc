<?php
/**
 * Class to extend the encapsulate access to dokuwiki plugins
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
    protected $metaDataSubSet = '';
    protected $projectOwner = '';
    protected $projectSourceType= '';
    protected $persistenceEngine;
    protected $modelManager;

    /**
     * Populates the parent master list of plugins and add projects
     */
    public function __construct() {
        parent::__construct();
        $this->_populateMasterListProjects();
        $this->metaDataSubSet = ProjectKeys::VAL_DEFAULTSUBSET;
    }
    public function getList($type='', $all=false) {
        $parenListByType = parent::getList($type, $all);    // request the complete plugin list
        if (!$type) return $parenListByType;  //[JOSEP] ALERTA: Així només retornarà la llista sencera, però exceptuant els plugins dels projectes. Cal afegir la llista complerta inclosos els projectes
        if (!isset($this->list_byProjectType[$type]['enabled']))
            $this->list_byProjectType[$type]['enabled'] = $this->_getListByProjectType($type,true);
        if ($all && !isset($this->list_byProjectType[$type]['disabled']))
            $this->list_byProjectType[$type]['disabled'] = $this->_getListByProjectType($type,false);
        return $all ? array_merge($parenListByType,$this->list_byProjectType[$type]['enabled'],$this->list_byProjectType[$type]['disabled']) : array_merge($parenListByType,$this->list_byProjectType[$type]['enabled']);
    }
    
    public function setCurrentProject($params) {
        if(isset($params[AjaxKeys::PROJECT_TYPE])){
            $this->currentProject = $params[AjaxKeys::PROJECT_TYPE];
        }else{
            $this->currentProject = AjaxKeys::VAL_DEFAULTPROJECTTYPE;
        }
        $this->projectSourceType = $params[AjaxKeys::PROJECT_SOURCE_TYPE];
        $this->projectOwner      = $params[AjaxKeys::PROJECT_OWNER];
        if(isset( $params[AjaxKeys::METADATA_SUBSET])){
            $this->metaDataSubSet    = $params[AjaxKeys::METADATA_SUBSET];
        }
//        $this->projectTypeDir    = $params[AjaxKeys::PROJECT_TYPE_DIR];
    }

    public function getProjectOwner() {
        return $this->projectOwner;
    }

    public function getProjectSourceType() {
        return $this->projectSourceType;
    }

    public function getCurrentProject() {
        return $this->currentProject;
    }

    public function getProjectTypeDir($projectType=FALSE) {
        $trobat=FALSE;
        $projectTypeDir;
        if(!$projectType){
            $projectType = $this->getCurrentProject();
        }
        $projectPlugins = array_keys($this->tmp_projects);
        for($ind=0; !$trobat && $ind<count($projectPlugins); $ind++){
            $projectTypeDir = DOKU_PLUGIN. $projectPlugins[$ind]."/projects/".$projectType."/";
            $trobat = file_exists($projectTypeDir."metadata/config/configMain.json");
        }
        if(!$trobat){
            throw new UnknownPojectTypeException($projectType);
        }
        return $projectTypeDir;
    }

    public function setPersistenceEngine($persistenceEngine) {
        $this->persistenceEngine = $persistenceEngine;
    }

    public function getProjectFile($projectOwner=NULL, $projectSourceType=NULL) {
        if(!$projectOwner){
            $projectOwner = $this->projectOwner;
        }
        if(!$projectSourceType){
            $projectSourceType = $this->projectSourceType;
        }

        if ($projectOwner && $this->persistenceEngine) {

            $model = new BasicWikiDataModel($this->persistenceEngine);
            $query = $model->getProjectMetaDataQuery();
            $query->init($projectOwner, $this->metaDataSubSet, $projectSourceType);
//            $param = array(ProjectKeys::KEY_PROJECT_TYPE => $projectSourceType, ProjectKeys::KEY_METADATA_SUBSET=> $this->metaDataSubSet);
            $data = $query->getFileName($projectOwner, $param);

            return $data;
        } else {
            throw new Exception("Project or persistence not specified");
        }

    }

    public function getCurrentProjectDataSource($projectOwner=FALSE, $projectSourceType=FALSE, $subset=FALSE) {
        if(!$projectOwner){
            $projectOwner = $this->projectOwner;
        }
        if(!$projectSourceType){
            $projectSourceType = $this->projectSourceType;
        }
        if(!$subset){
            $subset = $this->metaDataSubSet;
        }
        if ($projectOwner && $this->persistenceEngine) {

            $model = new BasicWikiDataModel($this->persistenceEngine);
            $query = $model->getProjectMetaDataQuery();
            $data = $query->init($projectOwner, $subset,  $projectSourceType)->getDataProject();
            return $data;
        }else {
            throw new Exception("Project or persistence not specified");
        }
    }


//JOSEP: AQUESTA FUCIÓ NO LA FA SERVIR NINGÚ
//    /**
//     * ALERTA[Xavi] Copiat fil per randa de ProjectExportAction.php
//     *
//     * Extrae, del contenido del fichero, los datos correspondientes a la clave
//     * @param string $file : ruta completa al fichero de datos del proyecto
//     * @param string $metaDataSubSet : clave del contenido
//     * @return array conteniendo el array de la clave 'metadatasubset' con los datos del proyecto
//     */
//    private function getProjectDataFile($file, $metaDataSubSet) {
//        $contentFile = @file_get_contents($file);
//        if ($contentFile != false) {
//            $contentArray = json_decode($contentFile, true);
//            return $contentArray[$metaDataSubSet];
//        }
//    }

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
