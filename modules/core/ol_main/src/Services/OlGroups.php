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
   * OlMembers constructor.
   *
   * @param $route
   * @param $messenger
   * @param $current_user
   */
  public function __construct($route, $messenger, $current_user) {
    $this->route = $route;
    $this->messenger = $messenger;
    $this->current_user = $current_user;
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
   * @return mixed
   */
  public function getGroups(){
    // Get groups data.
    $uid = $this->current_user->id();
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'name');
    $query->addField('gr', 'id');
    $query->addField('gr', 'landing');
    $query->condition('gr.status', 1);
    $query->condition('lgu.member_uid', $uid);
    $query->orderBy('gr.type', 'desc');
    $query->orderBy('gr.name', 'asc');
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    return $query->execute()->fetchAll();
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
  public function getGroupName($gid) {
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
   * @param $name
   * @param $sections
   * @param $homepage
   * @param null $gid
   */
  public function saveGroupSettings($name, $sections, $homepage, $gid = null, $on_top = null){
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
    // Save group settings.
    \Drupal::database()->update('ol_group')
      ->fields([
        'name' => $name,
        'enabled_sections' => implode(',', $enabled_sections_array),
        'landing' => $homepage,
        'type' => $on_top,
      ])
      ->condition('id', $gid, '=')
      ->execute();
    \Drupal::messenger()->addStatus(t('Your group settings were saved successfully.'));
  }

  /**
   * @return bool
   */
  public function isOnTop(){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->condition('gr.id', $gid);
    $query->condition('gr.type', 99); // 99 = 'on top'.
    return ($query->execute()->fetchField()) ? true : false;
  }

  /**
   * @return bool
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

}
