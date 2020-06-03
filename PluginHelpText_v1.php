<?php
class PluginHelpText_v1{
  public $settings;
  public $mysql;
  private $i18n;
  function __construct($buto) {
    if($buto){
      wfPlugin::enable('wf/form_v2');
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('wf/mysql');
      $this->mysql =new PluginWfMysql();
      $this->settings = wfPlugin::getPluginSettings('help/text_v1', true);
      /**
       * 
       */
      wfPlugin::includeonce('i18n/translate_v1');
      $this->i18n = new PluginI18nTranslate_v1();
      $this->i18n->path = '/plugin/help/text_v1/i18n';
    }
  }
  public function getElement($name){
    return new PluginWfYml(__DIR__."/element/$name.yml");
  }
  public function getForm($name){
    return new PluginWfYml(__DIR__."/form/$name.yml");
  }
  public function getSql($key){
    return new PluginWfYml(__DIR__.'/mysql/sql.yml', $key);
  }
  public function form_render($form){
    $form = new PluginWfArray($form);
    $rs = $this->db_text_select_one(wfRequest::get('id'));
    $form->set('items/id/default', wfRequest::get('id'));
    $form->set('items/headline/default', $rs->get('headline'));
    $form->set('items/description/default', $rs->get('description'));
    return $form->get();
  }
  public function form_capture(){
    $rs = $this->db_text_select_one(wfRequest::get('id'));
    if(!$rs->get('id')){
      $this->db_text_insert(wfRequest::get('id'));
    }
    $this->db_text_update();
    if(wfRequest::get('clear')){
      $this->db_confirm_delete_by_text(wfRequest::get('id'));
    }
    return array("$('#modal_helptext').modal('hide');alert('".$this->i18n->translateFromTheme('helptext_i18n_item_was_saved')."');");
  }
  public function db_open(){
    $this->mysql->open($this->settings->get('data/mysql'));
  }
  private function db_text_select_one($id){
    $sql = $this->getSql('text_select_one');
    $sql->set('params/id/value', $id);
    $this->db_open();
    $this->mysql->execute($sql->get());
    $rs = new PluginWfArray($this->mysql->getStmtAsArrayOne());
    return $rs;
  }
  private function db_text_select_list(){
    $confirm = $this->db_confirm_select_list();
    $sql = $this->getSql('text_select_list');
    $this->db_open();
    $this->mysql->execute($sql->get());
    $rs = $this->mysql->getStmtAsArray();
    foreach ($rs as $key => $value) {
      /**
       * Session.
       */
      $rs[$key]['in_session'] = $this->session_exist($value['id'])?'Yes':'No';
      /**
       * Db.
       */
      $is_confirm = false;
      foreach ($confirm as $key2 => $value2) {
        if($value2['helptext_text_id']==$value['id']){
          $is_confirm = true;
          break;
        }
      }
      $rs[$key]['is_confirm'] = $is_confirm?'Yes':'No';
    }
    return $rs;
  }
  
  private function db_text_insert($id){
    $sql = $this->getSql('text_insert');
    $this->db_open();
    $sql->set('params/id/value', $id);
    $this->mysql->execute($sql->get());
    return null;
  }
  private function db_text_update(){
    $sql = $this->getSql('text_update');
    $this->db_open();
    $sql->set('params/id/value', wfRequest::get('id'));
    $sql->set('params/headline/value', wfRequest::get('headline'));
    $sql->set('params/description/value', wfRequest::get('description'));
    $this->mysql->execute($sql->get());
    return null;
  }
  private function db_confirm_insert($helptext_text_id){
    /**
     * Check if text exist.
     */
    $text = $this->db_text_select_one($helptext_text_id);
    if(!$text->get('id')){
      return null;
    }
    /**
     * If confirm we set record in db.
     * Only if signed in.
     */
    if(wfRequest::get('confirm')=='yes' && wfUser::hasRole('client')){
      /**
       * Check if previos record exist.
       */
      $this->db_open();
      $sql = $this->getSql('confirm_select_one');
      $sql->set('params/helptext_text_id/value', $helptext_text_id);
      $this->mysql->execute($sql->get());
      $rs = new PluginWfArray($this->mysql->getStmtAsArrayOne());
      if(!$rs->get('account_id')){
        /**
         * Save.
         */
        $sql = $this->getSql('confirm_insert');
        $sql->set('params/helptext_text_id/value', $helptext_text_id);
        $this->mysql->execute($sql->get());
      }
    }
    /**
     * Set session.
     */
    $this->session_set(array('helptext_text_id' => $helptext_text_id, 'created_at' => date('Y-m-d H:i:s')));
    return null;
  }
  private function db_confirm_select_list(){
    $this->db_open();
    $sql = $this->getSql('confirm_select_list');
    $this->mysql->execute($sql->get());
    return $this->mysql->getStmtAsArray();
  }
  private function db_confirm_select_by_text_count($helptext_text_id){
    $this->db_open();
    $sql = $this->getSql('confirm_select_by_text_count');
    $sql->set('params/helptext_text_id/value', $helptext_text_id);
    $this->mysql->execute($sql->get());
    return new PluginWfArray($this->mysql->getStmtAsArrayOne());
  }
  private function db_confirm_delete_by_text($helptext_text_id){
    $this->db_open();
    $sql = $this->getSql('confirm_delete_by_text');
    $sql->set('params/helptext_text_id/value', $helptext_text_id);
    $this->mysql->execute($sql->get());
    return null;
  }
  private function db_confirm_delete(){
    $this->db_open();
    $sql = $this->getSql('confirm_delete');
    $this->mysql->execute($sql->get());
    return null;
  }
  private function session_set($value){
    $_SESSION['plugin']['help']['text_v1']['confirm'][$value['helptext_text_id']] = $value['created_at'];
    return null;
  }
  private function session_exist($helptext_text_id){
    if(isset($_SESSION['plugin']['help']['text_v1']['confirm'][$helptext_text_id])){
      return $_SESSION['plugin']['help']['text_v1']['confirm'][$helptext_text_id];
    }else{
      return null;
    }
  }
  private function session_clear(){
    unset($_SESSION['plugin']['help']['text_v1']['confirm']);
  }
  public function widget_helptext($data){
    /**
     * Plugin data.
     */
    $data = new PluginWfArray($data);
    /**
     * Mysql data.
     */
    $rs = $this->db_text_select_one($data->get('data/id'));
    $rs->set('id_collapse', 'helptext_collapse_'.$rs->get('id'));
    /**
     * 
     */
    $rs->set('helptext_id', 'helptext_'.$rs->get('id'));
    $rs->set('collapse_id', 'collapse_'.$rs->get('id'));
    $rs->set('HT-collapse_id', '#collapse_'.$rs->get('id'));
    /**
     * If record is missing.
     */
    if(!$rs->get('id') && wfUser::hasRole('webmaster')){
      $rs->set('id', $data->get('data/id'));
      $rs->set('headline', '_');
      $rs->set('description', '_');
    }elseif(!$rs->get('id')){
      throw new Exception('PluginHelpText_v1 says: Param id is empty.');
    }
    /**
     * 
     */
    $rs->set('description', str_replace("\n", '<br>', $rs->get('description')));
    /**
     * Check session.
     */
    if($this->session_exist($rs->get('id'))){
      $rs->set('confirm', true);
      $rs->set('created_at', $this->session_exist($rs->get('id')));
    }else{
      $rs->set('confirm', false);
      $rs->set('created_at', null);
    }
    /**
     * Element.
     */
    $element = $this->getElement('helptext');
    /**
     * Set element.
     */
    $element->setByTag($rs->get());
    /**
     * Render.
     */
    wfDocument::renderElement($element->get());
  }
  public function page_confirm(){
    $this->db_confirm_insert(wfRequest::get('id'));
    exit;
  }
  public function page_clear(){
    $this->session_clear();
    if(wfUser::hasRole('client')){
      $this->db_confirm_delete();
    }
    exit('Text handled of PluginHelpText_v1 is now removed in session.');
  }
  public function page_helptext(){
    $clear = $this->getElement('clear');
    $box = $this->getElement('box');
    $rs = $this->db_text_select_list();
    $element = array();
    foreach ($rs as $key => $value) {
      $item = new PluginWfArray($value);
      $box->setById('helptext_text_headline', 'innerHTML', $item->get('headline'));
      $box->setById('helptext_text_description', 'innerHTML', $item->get('description'));
      $box->setById('helptext_text_place', 'innerHTML', $item->get('place'));
      /**
       * 
       */
      $text = null;
      if($item->get('is_confirm')=='Yes'){
        $text = $this->i18n->translateFromTheme('Permanent');
      }elseif($item->get('in_session')=='Yes'){
        $text = $this->i18n->translateFromTheme('Temporary');
      }else{
        $text = $this->i18n->translateFromTheme('No');
        continue;
      }
      $box->setById('confirmed', 'innerHTML', $text);
      $element[] = $box->get('box');
    }
    /**
     * Clear button.
     */
    if(sizeof($element)==0){
      $clear->setById('btn_helptext_clear', 'attribute/style', 'display:none');
    }
    /**
     * Render.
     */
    $clear->setByTag(array('sizeof' => sizeof($element)));
    wfDocument::renderElement($clear->get());
    wfDocument::renderElement($element);
    
  }
  public function page_form(){
    if(!wfUser::hasRole('webmaster')){
      exit('');
    }
    /**
     * 
     */
    $rs = $this->db_confirm_select_by_text_count(wfRequest::get('id'));
    /**
     * 
     */
    $form = $this->getForm('text');
    $form->setByTag($rs->get());
    $widget = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function page_capture(){
    if(!wfUser::hasRole('webmaster')){
      exit('');
    }
    $form = $this->getForm('text');
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function event_signin(){
    $rs = $this->db_confirm_select_list();
    foreach ($rs as $key => $value) {
      $this->session_set($value);
    }
    return null;
  }
}
