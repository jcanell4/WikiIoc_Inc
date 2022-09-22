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
    protected $projectSourceType = '';
    protected $currentProjectVersions = array();
    protected $persistenceEngine;
    protected $modelManager;
    protected $id;
    protected $actionCommand;

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

    public function setCurrentProject($params) {//[JOSEP]. TODO => RAFA: Cal canviar de nom aquesta funció. S'ha d'anomenar setCurrentProjectType
        $this->id = $params[AjaxKeys::KEY_ID];
        if (isset($params[AjaxKeys::PROJECT_TYPE])){
            $this->currentProject = $params[AjaxKeys::PROJECT_TYPE];
        }else{
            $type = $this->getProjectTypeFromProjectId($this->id, TRUE);
            if($type){
                $this->currentProject = $type;
            }else{
                $this->currentProject = AjaxKeys::VAL_DEFAULTPROJECTTYPE;
            }
        }
        $this->projectSourceType = $params[AjaxKeys::PROJECT_SOURCE_TYPE];
        $this->projectOwner      = $params[AjaxKeys::PROJECT_OWNER];
        if (isset($params[AjaxKeys::METADATA_SUBSET])){
            $this->metaDataSubSet = $params[AjaxKeys::METADATA_SUBSET];
        }
        $this->actionCommand = $params[AjaxKeys::KEY_ACTION];
        $this->currentProjectVersions = $this->getCurrentProjectVersions(NULL, FALSE, AjaxKeys::VAL_DEFAULTSUBSET, TRUE);
    }

    // projectId del projecte quan el call prové des d'un document que pertany a un projecte
    public function getProjectOwner() {
        return $this->projectOwner;
    }

    // tipus de projecte quan el call prové d'un document que pertany a un projecte
    public function getProjectSourceType() {
        return $this->projectSourceType;
    }

    // Tipus de projecte quan el call prové del formulari
    public function getCurrentProject() {//[JOSEP]. TODO => RAFA: Cal canviar de nom aquesta funció. S'ha d'anomenar getCurrentProjectType
        return $this->currentProject;
    }

    public function getProjectTypeFromProjectId($projectId, $force=FALSE) {
         if ($this->persistenceEngine) {
            $model = new BasicWikiDataModel($this->persistenceEngine);
            $query = $model->getProjectMetaDataQuery();
            $ret = $query->getProjectType($projectId);
        } elseif($force){
            $model = new BasicWikiDataModel(new BasicPersistenceEngine());
            $query = $model->getProjectMetaDataQuery();
            $ret = $query->getProjectType($projectId);
        } else {
            throw new Exception("Persistence not specified");
        }
        return $ret;
    }

    // Tipus de projecte independentment del call
    public function getProjectType() {
        $projectSourceType = $this->getProjectSourceType();

        if (strlen($projectSourceType)>0) {
            return $projectSourceType;
        } else {
            return $this->getCurrentProject();
        }
    }

    public function getProjectTypeDir($projectType=FALSE) {
        $trobat = FALSE;
        $projectTypeDir = NULL;
        if(!$projectType){
            //
            //ATENCIÓ: vigilar que aquest canvi funcioni bé per a projectes i per a pages
            //
            //$projectType = $this->getCurrentProject();
            $projectType = $this->getProjectType();
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

    /**
     *
     * @param string  $att : Atribut solicitat de metaDataProjectStructure:versions de configMain.json
     * @param string  $projectType : Tipus de projecte
     * @param string  $metaDataSubSet : metaDataSubSet del projecte
     * @param boolean $force : indica si s'ha de forçar la lectura de configMain.json
     * @return int|array : conté el valor per a 'fields' o el array per a 'templates' o el array 'versions' o NULL
     */
    public function getCurrentProjectVersions($att=NULL, $projectType=FALSE, $metaDataSubSet=AjaxKeys::VAL_DEFAULTSUBSET, $force=FALSE) {
        if (!$force && !empty($this->currentProjectVersions)) {
            $ret = $this->currentProjectVersions;
        }else {
            if (!$projectType) {
                $projectType = $this->getProjectType();
            }
            $projectTypeDir = $this->getProjectTypeDir($projectType);
            if ($projectTypeDir) {
                $config = @file_get_contents($projectTypeDir."metadata/config/configMain.json");
                if ($config != FALSE) {
                    $struc = IocCommon::toArrayThroughArrayOrJson($config);
                    $elem = $struc[ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE];
                    for ($i=0; $i<count($elem); $i++) {
                        if (array_key_exists($metaDataSubSet, $elem[$i])) {
                            $ret = $elem[$i]['versions'];
                            break;
                        }
                    }
                }
            }
        }
        return ($att) ? $ret[$att] : $ret;
    }

    public function getPersistenceEngine(){
        return $this->persistenceEngine;
    }

    public function setPersistenceEngine($persistenceEngine) {
        $this->persistenceEngine = $persistenceEngine;
    }

    public function get_ftpsend_metadata($projectId=NULL, $projectSourceType=NULL, $metadataSubset=Projectkeys::VAL_DEFAULTSUBSET) {
        global $ID;

        if(!$projectId){
            $projectId = $this->projectOwner?$this->projectOwner:$ID;
        }
        if(!$projectSourceType){
            $projectSourceType = $this->projectSourceType?$this->projectSourceType:$this->currentProject;
        }

        if ($projectId && $this->persistenceEngine) {
            $modelClass = $projectSourceType."ProjectModel";
            $model = new $modelClass($this->persistenceEngine);
            $model->init($projectId, $projectSourceType, NULL, "defaultView", $metadataSubset);
            $data = $model->get_ftpsend_metadata(FALSE);
        } else {
            throw new Exception("Project or persistence not specified");
        }
        return $data;
    }

    public function getProjectFile($projectOwner=NULL, $projectSourceType=NULL) {//[JOSEP] ALERTA => RAFA: Quifa servir aquesta funció? Si és interna caldria fer.la private. Si es fa servir fora d'aquí, caldria buscar alternatuva. Proposo fer llista d'usos.
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
            $data = $query->getFileName($projectOwner, $param);
        } else {
            throw new Exception("Project or persistence not specified");
        }
        return $data;
    }
    
    public function canProjectOwnerAllowEditionDocument($id){
        $ret = TRUE;
        if(!empty($this->getProjectOwner())){
            $projectModel = $this->getAnotherProjectModel($this->getProjectOwner(), $this->getProjectSourceType());
            $ret = $projectModel->canDocumentBeEdited($id);
        }
        return $ret;
    }
    
    public function isProjectOwnerTypeWorkflow(){
        if ($this->persistenceEngine  && !empty($this->getProjectSourceType())) {
            $projectDir = $this->getProjectTypeDir($this->getProjectSourceType());
            $ret = file_exists($projectDir."metadata/config/workflow.json");
            return $ret;
        }else {
            if($this->persistenceEngine ){
                throw new Exception("El documen no pertany a acap projecte");
            }else{
                throw new Exception("Project or persistence not specified");
            }
        }
    }

    public function getCurrentProjectModel($subset=FALSE) {
        if (!$subset) $subset = $this->metaDataSubSet;
        if ($this->persistenceEngine) {
            $projectDir = $this->getProjectTypeDir($this->currentProject);
            $ownProjectModel = $this->currentProject."ProjectModel";
            if (!class_exists($ownProjectModel, false)){
                require_once $projectDir."datamodel/".$ownProjectModel.".php";
            }
            $projectModel = new $ownProjectModel($this->persistenceEngine);
            $projectModel->init($this->id, $this->currentProject, NULL, NULL, $subset, $this->actionCommand);
            return $projectModel;
        }else {
            throw new Exception("Project or persistence not specified");
        }
    }

    /**
     * Retorna una instancia al modelo del tipo de proyecto indicado
     * @param string $id : wiki ruta (ns) del proyecto
     * @param string $projectType : tipo de proyecto
     * @param string $subset
     * @return \model
     * @throws Exception
     */
    public function getAnotherProjectModel($id, $projectType, $subset=FALSE) {
        if (!$subset) $subset = ProjectKeys::VAL_DEFAULTSUBSET;
        if ($this->persistenceEngine) {
            $projectDir = $this->getProjectTypeDir($projectType);
            $model = "{$projectType}ProjectModel";
            if (!class_exists($model, false)){
                require_once "{$projectDir}datamodel/{$model}.php";
            }
            $projectModel = new $model($this->persistenceEngine);
            $projectModel->init($id, $projectType, NULL, NULL, $subset);
            return $projectModel;
        }else {
            throw new Exception("Project or persistence not specified");
        }
    }

    public function getProjectDataSourceFromProjectId($projectId, $projectSourceType=FALSE, $subset=FALSE) {
         if(!$projectSourceType){
            $projectSourceType = $this->getProjectTypeFromProjectId($projectId);
        }
        if(!$subset){
            $subset = ProjectKeys::VAL_DEFAULTSUBSET;
        }
        if ($projectId && $this->persistenceEngine) {//[JOSEP] TODO => RAFA: Substituir aquest bloc de codi pel mètode getCurrentProjectModel

            $projectDir = $this->getProjectTypeDir($projectSourceType );
            $ownProjectModel = $projectSourceType."ProjectModel";
            if(!class_exists($ownProjectModel, false)){
                require_once $projectDir."datamodel/".$ownProjectModel.".php";
            }
            $projectModel = new $ownProjectModel($this->persistenceEngine);
            $data = $projectModel->getDataProject($projectId, $projectSourceType, $subset);
            return $data;
        }else {
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
        return $this->getProjectDataSourceFromProjectId($projectOwner, $projectSourceType, $subset);
//        if ($projectOwner && $this->persistenceEngine) {//[JOSEP] TODO => RAFA: Substituir aquest bloc de codi pel mètode getCurrentProjectModel
//
//            $projectDir = $this->getProjectTypeDir($projectSourceType );
//            $ownProjectModel = $projectSourceType."ProjectModel";
//            if(!class_exists($ownProjectModel, false)){
//                require_once $projectDir."datamodel/".$ownProjectModel.".php";
//            }
//            $projectModel = new $ownProjectModel($this->persistenceEngine);
//            $data = $projectModel->getDataProject($projectOwner, $projectSourceType, $subset);
//
////            $model = new BasicWikiDataModel($this->persistenceEngine);
////            $query = $model->getProjectMetaDataQuery();
////            $data = $query->init($projectOwner, $subset,  $projectSourceType)->getDataProject();
//            return $data;
//        }else {
//            throw new Exception("Project or persistence not specified");
//        }
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
        // ALERTA! [Xavi] Comprovem si existeix el SourceType per carregar correctament els plugins corresponents a la
        // sintaxi dintre de projectes. En aquest cas es fa servir el projectSourceType ja que el currentProject és default.
        return "$plugin/projects/" . $this->getProjectType();
    }

    private function _nameTransform($name) {
        return str_replace('/', '_', $name);
    }

    private function _populateMasterListProjects() {
        if (($dh = @opendir(DOKU_PLUGIN))) {
            $all_plugin_projects = array();
            while (false !== ($project = readdir($dh))) {
                if ($project[0] == '.' || is_file(DOKU_PLUGIN.$project)) continue; // skip hidden entries and files, we're only interested in directories
                if (@file_exists(DOKU_PLUGIN.$project.'/projects'))
                    $all_plugin_projects[$project] = 1;
            }
            $this->tmp_projects = $all_plugin_projects;
        }
    }

}
