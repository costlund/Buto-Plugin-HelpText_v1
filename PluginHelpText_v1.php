<?php
class PluginHelpText_v1{
// <editor-fold defaultstate="collapsed" desc="Variables">
  public $settings;
  public $mysql;
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Construct">
  function __construct($buto) {
    if($buto){
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('wf/mysql');
      $this->mysql =new PluginWfMysql();
      $this->settings = wfPlugin::getPluginSettings('help/text_v1', true);
    }
  }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Methods">
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
    $rs = $this->db_text_select_one(wfRequest::get('id'));
    $form->set('items/id/default', wfRequest::get('id'));
    $form->set('items/headline/default', $rs->get('headline'));
    $form->set('items/description/default', $rs->get('description'));
    return $form;
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
    return array("$('#modal_helptext').modal('hide');alert('Item was saved. Please update page where helptext occured.');");
  }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Database">
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
    $this->session_set($helptext_text_id);
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
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Session">
  private function session_set($helptext_text_id){
    $_SESSION['plugin']['help']['text_v1']['confirm'][$helptext_text_id] = true;
    return null;
  }
  private function session_exist($helptext_text_id){
    return isset($_SESSION['plugin']['help']['text_v1']['confirm'][$helptext_text_id]);
  }
  private function session_clear(){
    unset($_SESSION['plugin']['help']['text_v1']['confirm']);
  }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Widget">
  public function widget_helptext($data){
    /**
     * Plugin data.
     */
    $data = new PluginWfArray($data);
    /**
     * Mysql data.
     */
    $rs = $this->db_text_select_one($data->get('data/id'));
    /**
     * If record is missing.
     */
    if(!$rs->get('id') && wfUser::hasRole('webmaster')){
      //echo '<p><i>Webmaster: Could not find any record in table helptext_text with id '.$data->get('data/id').'.</i></p>';
      $rs->set('id', $data->get('data/id'));
      $rs->set('headline', '_');
      $rs->set('description', '_');
      //return null;
    }elseif(!$rs->get('id')){
      return null;
    }
    /**
     * Check session.
     */
    if($this->session_exist($rs->get('id'))){
      return null;
    }
    /**
     * Element.
     */
    $element = $this->getElement('helptext');
    /**
     * Set element.
     */
    $element->setById('helptext_text_headline', 'innerHTML', $rs->get('headline'));
    $element->setById('helptext_text_description', 'innerHTML', $rs->get('description'));
    $element->setById('helptext_', 'attribute/data-id', $rs->get('id'));
    $element->setById('helptext_', 'attribute/id', 'helptext_'.$rs->get('id'));
    //$element->setById('btn_confirm', 'attribute/data-id', 'helptext_'.$rs->get('id'));
    $script = $element->getById('script', 'innerHTML');
    $script = str_replace('#helptext_', '#helptext_'.$rs->get('id'), $script->get());
    $script = $element->setById('script', 'innerHTML', $script);
    /**
     * Render.
     */
    wfDocument::renderElement($element->get());
  }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Page">
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
    //wfHelp::yml_dump($rs);
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
      if($item->get('is_confirm')=='Yes' && $item->get('in_session')=='Yes'){
        $text = 'Yes';
      }elseif($item->get('is_confirm')=='Yes'){
        $text = 'Only in database';
      }elseif($item->get('in_session')=='Yes'){
        $text = 'Only in session';
      }else{
        $text = 'No';
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
    wfDocument::renderElement($clear->get());
    wfDocument::renderElement($element);
    
  }
  public function page_form(){
    if(!wfUser::hasRole('webmaster')){
      exit('');
    }
    $form = $this->getForm('text');
    $widget = wfDocument::createWidget('wf/form_v2', 'render', $form->get());
    wfDocument::renderElement(array($widget));
    
    $rs = $this->db_confirm_select_by_text_count(wfRequest::get('id'));
    $element = array();
    $element[] = wfDocument::createHtmlElement('p', array(wfDocument::createHtmlElement('span', 'User confirms:'), wfDocument::createHtmlElement('span', $rs->get('count'))));
    wfDocument::renderElement($element);
  }
  public function page_capture(){
    if(!wfUser::hasRole('webmaster')){
      exit('');
    }
    $form = $this->getForm('text');
    $widget = wfDocument::createWidget('wf/form_v2', 'capture', $form->get());
    wfDocument::renderElement(array($widget));
  }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Event">
  public function event_signin(){
    $rs = $this->db_confirm_select_list();
    foreach ($rs as $key => $value) {
      $this->session_set($value['helptext_text_id']);
    }
    return null;
  }
// </editor-fold>
}
