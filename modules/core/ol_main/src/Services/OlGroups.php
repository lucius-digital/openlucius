<?php

namespace Drupal\ol_main\Services;


use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
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
   * OlMembers constructor.
   *
   * @param $route
   * @param $messenger
   * @param $current_user
   * @param $renderer
   */
  public function __construct($route, $messenger, $current_user, $renderer) {
    $this->route = $route;
    $this->messenger = $messenger;
    $this->current_user = $current_user;
    $this->renderer = $renderer;
  }

  /**
   * @param $name
   * @param null $uid
   *
   * @param bool $message_redirect
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addGroup($name, $uid = null, $message_redirect = true){
    // Get uid if arument is empty.
    $uid = (empty($uid)) ? $this->current_user->id(): $uid;
    // Create Group.
    $ol_group = OlGroup::create([
      'name' => Html::escape($name),
      'type' => 1,
      'landing' => 'stream',
      'enabled_sections' => 'stream,members,files,messages',
      'user_id' => $uid,
    ]);
    $ol_group->save();
    $new_group_id = $ol_group->id();

    // Add user to Group.
    $ol_group = OlGroupUser::create([
      'name' => $name,
      'group_id' => $new_group_id,
      'member_uid' => $uid,
    ]);
    $ol_group->save();
    if($message_redirect === true) {
      // Redirect with message.
      $this->messenger->addStatus(t('Your group was created successfully!'));
      $this->messenger->addStatus(t('You can now configure it in the form below:'));
      $path = Url::fromRoute('ol_main.group_settings', ['gid' => $new_group_id])
        ->toString();
      $response = new RedirectResponse($path);
      $response->send();
    }
    return $new_group_id;
  }

  /**
   * @param int $status
   * @return mixed
   */
  public function getGroups($status = 1){
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
    $query->orderBy('gr.type', 'desc');
    $query->orderBy('gr.name', 'asc');
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    return $query->execute()->fetchAll();
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
      if ($timestamp_stream_item > $timestamp_user_group) {
        // Count query stream items where created > user_group_timestamp
        // Non-chat
        $query = \Drupal::database()->select('ol_stream_item', 'osi');
        $query->addField('osi', 'id');
        $query->condition('osi.group_id', $gid);
        $query->condition('osi.created', $timestamp_user_group ,'>');
        $query->condition('osi.entity_type', 'chat' ,'!=');
        $group_data->non_chat_count =  $query->countQuery()->execute()->fetchField();
        // Chat, needed for different badge, to prevent badge-cluttering.
        $query = \Drupal::database()->select('ol_stream_item', 'osi');
        $query->addField('osi', 'id');
        $query->condition('osi.group_id', $gid);
        $query->condition('osi.created', $timestamp_user_group ,'>');
        $query->condition('osi.entity_type', 'chat');
        $group_data->chat_count =  $query->countQuery()->execute()->fetchField();
      }
    }
    return $groups_data;
  }

  /**
   * @param $gid
   * @return mixed
   */
  public function getGroupUuidById($gid) {
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
    return $query->execute()->fetchField();
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
    $on_top = $form_state->getValue('on_top')[1];
    $status = $form_state->getValue('status')[1];
    // Switch, because checked = 1 means archived, but status 0 is archived in dbase.
    $status = ($status == 1) ? 0 : 1;
    // Handle on_top setting.
    $on_top = ($on_top) ? 99 : 1 ; // 99 = on top | 1 = default.
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
        'type' => $on_top,
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
   * @return void
   */
  public function redirectToTopGroup(){
    // Get current uid.
    $uid = $this->current_user->id();
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->addField('gr', 'landing');
    //$query->condition('gr.type', 99); // 99 = 'on top'.
    $query->condition('lgu.member_uid', $uid);
    $query->orderBy('gr.type', 'desc'); // 99 = 'on top'.
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
  public function getHeaderImage() {
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
}
