<?php

namespace Drupal\ol_main\Services;


use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ol_general_settings\Entity\OlGeneralSettings;
use Drupal\ol_group\Entity\OlGroup;
use Drupal\ol_group_user\Entity\OlGroupUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OlGroups.
 */
class OlGroups{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $messenger
   */
  protected $messenger;

  /**
   * @var $current_user
   */
  protected $current_user;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * @var $members
   */
  protected $members;

  /**
   * OlMembers constructor.
   *
   * @param $route
   * @param $messenger
   * @param $current_user
   * @param $renderer
   * @param $members
   */
  public function __construct($route, $messenger, $current_user, $renderer, $members) {
    $this->route = $route;
    $this->messenger = $messenger;
    $this->current_user = $current_user;
    $this->renderer = $renderer;
    $this->members = $members;
  }


  /**
   * Defines group types.
   * @return array
   */
  public function getGroupTypes(){
    $group_types = array();
    $group_types['company'] = array(
      'label' => t('Company wide group'),
      'block_header' => t('Company wide'),
      'icon_class' => t('lni lni-apartment'),
      'weight' => 10,
    );
    $group_types['team'] = array(
      'label' => t('Team'),
      'block_header' => t('Teams'),
      'icon_class' => t('lni lni-users'),
      'weight' => 50,
    );
    $group_types['project'] = array(
      'label' => t('Project'),
      'block_header' => t('Projects'),
      'icon_class' => t('lni lni-rocket'),
      'weight' => 100,
    );
    // Let other modules alter group types.
    \Drupal::moduleHandler()->invokeAll('alter_group_types', [&$group_types]);
    return $group_types;
  }

  /**
   * @param string $name
   * @param string $type
   * @param int $uid
   * @param bool $message_redirect
   *
   * @param $enabled_sections
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addGroup($name, $type = 'team', $uid = null, $message_redirect = true, $enabled_sections = null, $status = 1){

    // Get uid if argument is empty.
    $uid = (empty($uid)) ? $this->current_user->id(): $uid;
    // Create Group.
    // Status = 10, is 'placeholder' group for global / homepage content
    $ol_group = OlGroup::create([
      'name' => Html::escape($name),
      'type' => $type,
      'landing' => 'stream',
      'enabled_sections' => $enabled_sections, // Todo: jsonize.
      'user_id' => $uid,
      'status' => $status,
    ]);
    $ol_group->save();
    $new_group_id = $ol_group->id();

    // Add all users to this company-wide group.
    if($type == 'company') {
      // Add all users to this group.
      $all_members = $this->members->getAllUsers();
      foreach ($all_members as $member){
        $ol_group = OlGroupUser::create([
          'name' => $name,
          'group_id' => $new_group_id,
          'member_uid' => $member->uid,
        ]);
        $ol_group->save();
      }
    }
    // Only add current user to this group, since it's not company wide.
    else {
      // Add user to Group.
      $ol_group = OlGroupUser::create([
        'name' => $name,
        'group_id' => $new_group_id,
        'member_uid' => $uid,
      ]);
      $ol_group->save();
    }
    if($message_redirect === true) {
      // Redirect with message.
      $this->messenger->addStatus(t('Created successfully!'));
      $this->messenger->addStatus(t('You can now configure it in the form below.'));
      $path = Url::fromRoute('ol_main.group_settings', ['gid' => $new_group_id])
        ->toString();
      $response = new RedirectResponse($path);
      $response->send();
    }
    return $new_group_id;
  }

  /**
   * @param int $status
   * @param null $type
   *
   * @return mixed
   */
  public function getGroups($status, $type = null){
    // Get groups data.
    $uid = $this->current_user->id();
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'name');
    $query->addField('gr', 'id');
    $query->addField('gr', 'landing');
    $query->addField('gr', 'user_id');
    $query->addField('gr', 'changed');
    $query->addField('gr', 'created');
    $query->condition('gr.status', $status);
    $query->condition('lgu.member_uid', $uid);
    $query->orderBy('gr.name', 'asc');
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    return $query->execute()->fetchAll();
  }



  /**
   * @return mixed
   */
  public function getUserGroupsIds(){
    // Get groups data.
    $uid = $this->current_user->id();
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->addField('lgu', 'member_uid');
    $query->condition('lgu.member_uid', $uid);
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    return $query->execute()->fetchAllKeyed();
  }


  /**
   * @param $groups_data
   * @return mixed
   */
  public function addActivityBadge($groups_data){
    // Loop through groups objects, add badge count if needed.
    foreach ($groups_data as $group_data) {
      // Get current user id.
      $uid = $this->current_user->id();
      $gid = $group_data->id;

      // Get user count.
      $query = \Drupal::database()->select('ol_group_user', 'ogu');
      $query->addField('ogu', 'id');
      $query->condition('ogu.group_id', $gid);
      $query->condition('ogu.status', 1);
      $group_data->user_count = $query->countQuery()->execute()->fetchField();

      // Get timestamp user last visited group
      $query = \Drupal::database()->select('ol_group_user', 'ogu');
      $query->addField('ogu', 'changed');
      $query->condition('ogu.group_id', $gid);
      $query->condition('ogu.member_uid', $uid);
      $timestamp_user_group =  $query->execute()->fetchField();

      // Get timestamp last stream_item in group
      $query = \Drupal::database()->select('ol_stream_item', 'osi');
      $query->addField('osi', 'created');
      $query->condition('osi.group_id', $gid);
      $query->orderBy('osi.id', 'desc');
      $timestamp_stream_item =  $query->execute()->fetchField();
      // Get new items for this user, if timestamps differ.
      if ($timestamp_stream_item > $timestamp_user_group ) {
        // Non-chat
        $query = \Drupal::database()->select('ol_stream_item', 'osi');
        $query->addField('osi', 'id');
        $query->condition('osi.group_id', $gid);
        $query->condition('osi.created', $timestamp_user_group ,'>');
        $query->condition('osi.entity_type', 'chat' ,'!=');
        $group_data->non_chat_count =  $query->countQuery()->execute()->fetchField();
      }

      // Get timestamp last chat_item in group.
      if (\Drupal::moduleHandler()->moduleExists('ol_chat')) {
        $query = \Drupal::database()->select('ol_chat_item', 'osi');
        $query->addField('osi', 'created');
        $query->condition('osi.group_id', $gid);
        $query->orderBy('osi.id', 'desc');
        $timestamp_chat_item = $query->execute()->fetchField();
        // Get new chat for this user, if timestamps differ.
        if ($timestamp_chat_item > $timestamp_user_group) {
          // Chat, needed for different badge, to prevent badge-cluttering.
          $query = \Drupal::database()->select('ol_chat_item', 'oci');
          $query->addField('oci', 'id');
          $query->condition('oci.group_id', $gid);
          $query->condition('oci.created', $timestamp_user_group, '>');
          $query->condition('oci.entity_type', 'chat');
          $group_data->chat_count = $query->countQuery()
            ->execute()
            ->fetchField();
        }
      }
    }
    return $groups_data;
  }

  /**
   * @param $gid
   * @return mixed
   */
  public function getGroupUuidById($gid = null) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'uuid');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * @param $uuid
   * @return mixed
   */
  public function getGroupIdByUuid($uuid) {
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->condition('gr.uuid', $uuid);
    return $query->execute()->fetchField();
  }

  /**
   * @return mixed
   */
  public function getCurrentGroupId() {
    return $this->route->getParameter('gid');
  }

  /**
   * @param $gid
   * @return mixed
   */
  public function getGroupName($gid = null) {
    // Get current gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'name');
    $query->condition('gr.id', $gid);
    return html::decodeEntities($query->execute()->fetchField());
  }

  /**
   * @param $gid
   * @return mixed
   */
  public function getGroupHome($gid) {
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'landing');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * @param null $form_state
   * @param null $options
   */
  public function saveGroupSettings($form_state = null, $options = null){
    // Get form values.
    $name = Html::escape($form_state->getValue('name'));
    $sections = $form_state->getValue('sections');
    $homepage = $form_state->getValue('homepage');
    //$on_top = $form_state->getValue('on_top')[1];
    $status = $form_state->getValue('status')[1];
    // Switch, because checked = 1 means archived, but status 0 is archived in dbase.
    $status = ($status == 1) ? 0 : 1;
    // Handle on_top setting.
    //$on_top = ($on_top) ? 99 : 1 ; // 99 = on top | 1 = default.
    // Get current gid.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Build array with only ticked values.
    $enabled_sections_array = array();
    foreach ($sections as $key => $section){
      if (!empty($section)){
        $enabled_sections_array[] = $key;
      }
    }
    // Build array with section override names.
    $sections_overrides = array();
    foreach ($options as $key => $section){
      if (!empty($section)){
        // Get override name.
        $override = $form_state->getValue('override_' .$key);
        // Build array if override name is provided.
        if(!empty($override)) {
          $sections_overrides[$key] = $override;
        }
      }
    }
    // Encode to json format.
    $sections_overrides_json = json_encode($sections_overrides);

    // Save group settings.
    \Drupal::database()->update('ol_group')
      ->fields([
        'name' => $name,
        'enabled_sections' => implode(',', $enabled_sections_array),
        'section_overrides' => $sections_overrides_json,
        'landing' => $homepage,
       // 'type' => $type,
        'status' => $status,
      ])
      ->condition('id', $gid, '=')
      ->execute();
    \Drupal::messenger()->addStatus(t('Your group settings were saved successfully.'));
  }

  /**
   * Returns if group has 'on top' checked.
   * @return bool
   */
  public function isOnTop(){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->condition('gr.id', $gid);
    $query->condition('gr.type', 99); // 99 = 'on top'.
    return ($query->execute()->fetchField()) ? true : false;
  }

  /**
   * Returns status of a group.
   * @return mixed
   */
  public function isArchived(){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'status');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Returns status of a group.
   *
   * @param null $gid
   * @return mixed
   */
  public function getGroupType($gid = null){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'type');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * @return void
   */
  public function _DISABLED_redirectToTopGroup(){
    // Get current uid.
    $uid = $this->current_user->id();
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->addField('gr', 'landing');
    //$query->condition('gr.type', 99); // 99 = 'on top'.
    $query->condition('lgu.member_uid', $uid);
    $query->condition('gr.status',1 );
    //$query->condition('gr.type', 'company');
    //$query->orderBy('gr.id', 'desc');
    $query->orderBy('gr.name', 'asc');
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    $query->range(0,1);
    $top_gid = $query->execute()->fetchObject();
    // Get landing page of top group.
    // Nasty, but else fromRoute would be too dynamic to build.
    global $base_url;
    $path = $base_url .'/group/'.$top_gid->id .'/'.$top_gid->landing;
    $response = new TrustedRedirectResponse($path);
    $response->send();
    exit; // Stop here, needed to prevent Notice message.
  }

  /**
   * Returns fid of current group header image.
   *
   * @return integer
   */
  public function getHeaderImage($gid = null) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->condition('olf.group_id', $gid);
    $query->condition('olf.entity_type', 'group_header');
    return $query->execute()->fetchField();
  }

  /**
   * @param $groups_data
   *
   * @return string
   */
  public function renderArchivedGroupsCards($groups_data){

    // Initiate html var.
    $groups_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($groups_data as $group){
      // Needed for 'group admin' badge.
      $groups_row_data['name'] = $group->name;
      $groups_row_data['id'] = $group->id;
      $groups_row_data['landing'] = $group->landing;
      $groups_row_data['admin_uid'] = $group->user_id;
      $groups_row_data['admin_name'] = $this->getGroupAdminName($group->id, $group->user_id);
      $groups_row_data['created'] = $group->created;
      $groups_row_data['changed'] = $group->changed;
      // Render the html row.
      $render = ['#theme' => 'groups_card', '#vars' => $groups_row_data];
      $groups_html .= $this->renderer->render($render);
    }
    return $groups_html;
  }

  /**
   * @param $gid
   * @return mixed
   */
  public function getGroupAdminName($gid, $uid){
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('ufd', 'name');
    $query->condition('gr.id', $gid);
    $query->join('users_field_data', 'ufd','ufd.uid = gr.user_id');
    return $query->execute()->fetchField();
  }

  /**
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function initiateGlobalGroup($id_new_user){
    // Add new 'global group' for home posts.
    $global_group_id = $this->addGroup('home_','global_group', $id_new_user,false,'', 10);
    // Save new group id to general settings.
    // Create settings entity
    $entity = OlGeneralSettings::create([
      'global_group_id' => $global_group_id,
      'user_id' => $id_new_user,
    ]);
    $entity->save();
    // Return group_id.
    return $global_group_id;
  }

  /**
   * @param $uid
   */
  public function addUserToGlobalGroup($uid){
    // Get global group id.
    $global_group_id = $this->getGlobalGroupId();
    // Add user to global group.
    $this->members->addUserToGroup($uid, $global_group_id, 'global_group');
  }

  /**
   * @return mixed
   */
  public function getGlobalGroupId(){
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'global_group_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    return $query->execute()->fetchField();
  }

  /**
   * @param $ordered_items
   *
   * @return bool
   */
  public function updateHomeTabsPositions($ordered_items){
    // Encode for database.
    $json_items  = json_encode($ordered_items);
    // For security hardening.
    $group_id = $this->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields([
          'tabs' => $json_items,
        ])
        ->condition('global_group_id', $group_id)
        ->execute();
      return true;
    }
    return false;
  }

  /**
   * @return mixed
   */
  public function getHomeTabsPositions(){
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'tabs');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    return json_decode($query->execute()->fetchField());
  }

}
