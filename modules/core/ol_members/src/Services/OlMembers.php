<?php

namespace Drupal\ol_members\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\ol_group_user\Entity\OlGroupUser;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OlMembers.
 */
class OlMembers{

  /**
  * @var \Drupal\Core\Database\Connection $database
  */
  protected $database;

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
   * @var $entity_type_manager
   */
  protected $entity_type_manager;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * OlMembers constructor.
   *
   * @param $route
   * @param $connection
   * @param $messenger
   * @param $current_user
   * @param $entity_type_manager
   * @param $renderer
   */
  public function __construct($route, $connection, $messenger, $current_user, $entity_type_manager, $renderer) {
    $this->database = $connection;
    $this->route = $route;
    $this->messenger = $messenger;
    $this->current_user = $current_user;
    $this->entity_type_manager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * @param $members_list_data
   * @param null $group_id
   * @param null $group_admin_uid
   * @return string
   * @throws \Exception
   */
  public function renderMembersCards($members_list_data, $group_id = null, $group_admin_uid = null, $blocked_users = null){

    // Initiate html var.
    $members_html = '';
    // Loop through array and render HTML rows via twig file.
    // Needed to show options drop down.
    $members_row_data['current_is_group_admin'] = (is_numeric($group_admin_uid)) ? TRUE : FALSE ;
    $current_user = User::load(\Drupal::currentUser()->id());
    $is_user_manager = $current_user->hasPermission('administer ol users');

    foreach ($members_list_data as $member){
      // Needed to show drop down item.
      $members_row_data['current_is_user_admin'] = $is_user_manager == true;
      // Needed for 'group admin' badge.
      $members_row_data['is_group_admin'] = $member->uid == $this->getGroupAdminUid();
      $members_row_data['user_picture'] = $this->getUserPictureUrl($member->uid);
      $user = User::load($member->uid);
      $members_row_data['is_user_admin'] = $user->hasPermission('administer ol users');
      // Get additional members data and build rows.
      $members_data = $this->getMemberData($member->uid);
      $members_row_data['name'] = $members_data->name;
      $members_row_data['group_id'] = $group_id;
      $members_row_data['uid'] = $members_data->uid;
      $members_row_data['mail'] = $members_data->mail;
      $members_row_data['status'] = $members_data->status;
      // Render the html row.
      $render = ['#theme' => 'members_card', '#vars' => $members_row_data];
      $members_html .= $this->renderer->render($render);
    }
    return $members_html;
  }

  /**
   * @param bool $exclude_current_uid
   *
   * @return mixed
   */
  public function getUsersInGroup($exclude_current_uid = false, $gid = null){

    // Get current group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Get and return data.
    $query = $this->database->select('ol_group_user', 'gu');
    $query->addField('gu', 'member_uid', 'uid');
    $query->addField('ufd', 'mail');
    $query->addField('ufd', 'name');
    $query->condition('gu.group_id', $gid);
    $query->condition('gu.status', 1);
    $query->condition('ufd.status', 1);
    if($exclude_current_uid == true){
      $query->condition('ufd.uid', $this->current_user->id(), '!=');
    }
    $query->join('users_field_data', 'ufd', 'ufd.uid = gu.member_uid');
    $query->orderBy('gu.name', 'asc');
    return $query->execute()->fetchAll();
  }

  /**
   * @param int $status
   *
   * @return mixed
   */
  public function getAllUsers($status = 1){
    // Get and return data.
    $query = $this->database->select('users_field_data', 'ufd');
    $query->addField('ufd', 'uid');
    $query->condition('ufd.status', $status);
    $query->orderBy('ufd.name', 'asc');
    $query->addTag('ol_user_list');
    return $query->execute()->fetchAll();
  }

  /**
   * @param $uid
   */
  public function deleteUserGroupRelation($uid){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Check if uid is group admin, that can't be removed.
    $group_admin_uid = $this->isGroupAdmin($uid);
    // Only remove if it's not group admin.
    if ($group_admin_uid == null) {
      // Remove user from group.
      $this->database->delete('ol_group_user')
        ->condition('group_id', $gid)
        ->condition('member_uid', $uid)
        ->execute();
      // Message and redirect.
      $this->messenger->addStatus(t('Member successfully removed from this group'));
    }
    if (is_numeric($group_admin_uid)){
      \Drupal::messenger()->addWarning( t('You can\'t remove group administrator from a group'));
    }
    $path = Url::fromRoute('ol_members.group_members',['gid' => $gid])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * @param null $user_id
   * @return int|null
   */
  public function isGroupAdmin($user_id = null){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Get current user data if non provided.
    $user_id = (empty($user_id)) ? $this->current_user->id() : $user_id;
    // Check if current user is group admin.
    $query = $this->database->select('ol_group', 'gr');
    $query->addField('gr', 'user_id');
    $query->condition('gr.user_id', $user_id);
    $query->condition('gr.id', $gid);
    $group_admin_uid = $query->execute()->fetchField();
    // Return group_admin_uid if user is group admin, else return null.
    return (is_numeric($group_admin_uid) && !empty($group_admin_uid)) ? $group_admin_uid : NULL;
  }

  /**
   * @param null $uid
   * @return string
   */
  public function getUserPictureUrl($uid = null){
    // Get uid if not provided.
    $uid = empty($uid) ? $this->current_user->id() : $uid;
    // Query user picture file uri
    $query = $this->database->select('user__field_user_picture', 'ufp');
    $query->addField('fileman', 'uri');
    $query->condition('ufp.entity_id', $uid);
    $query->join('file_managed', 'fileman', 'fileman.fid = ufp.field_user_picture_target_id');
    $file_uri = $query->execute()->fetchField();

    // Style image if it exists.
    if (!empty($file_uri)) {
      $style = $this->entity_type_manager->getStorage('image_style')->load('50x50');
      $picture_url = $style->buildUrl($file_uri);
    } else { // Default if user picture doesn't exist.
      global $base_url;
      $theme = \Drupal::theme()->getActiveTheme();
      $picture_url = $base_url.'/'. $theme->getPath() .'/images/default_user.jpg';
    }
    return $picture_url;
  }

  /**
   * @param $uid
   *
   * @return mixed
   */
  public function getUserName($uid = null){
    if(empty($uid)) {
      return $this->current_user->getAccountName();
    } else {
      $account = User::load($uid);
      return $account->getAccountName();
    }
  }
  /**
   * @param $email
   * @param $language
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addNewUser($email,$language ){
    $account = User::create();
    $account->enforceIsNew();
    $account->setPassword(user_password());
    $account->setEmail($email);
    $account->setUsername($email);
    $account->addRole('standard_user');
    $account->set('langcode', $language);
    $account->set('preferred_langcode', $language);
    $account->activate();
    $account->save();
    return $account;
  }

  /**
   * @param $uid
   * @param $gid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserToGroup($uid, $gid){
    // Create new relation.
    $ol_group_user = OlGroupUser::create([
      'name' => 'uid: '. $uid .' group id: '.$gid,
      'member_uid' => Html::escape($uid),
      'group_id' => $gid,
    ]);
    $ol_group_user->save();
  }

  /**
   * @param $uid
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserToCompanyWideGroups($uid){
    // Get all company-wide groups.
    $group_service = \Drupal::service('olmain.groups');
    $company_groups = $group_service->getGroups(1,'company');
    // Loop through company-wide groups and add user.
    foreach ($company_groups as $group){
      // Create new relation.
      $ol_group_user = OlGroupUser::create([
        'name' => 'uid: '. $uid .' group id: '.$group->id,
        'member_uid' => Html::escape($uid),
        'group_id' => $group->id,
      ]);
      $ol_group_user->save();
    }
  }
  /**
   * @param $uid
   * @param $role
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserRole($uid, $role){
    // Permission must be handled by route.
    // Load user and set role.
    $user = User::load($uid);
    $user->addRole($role);
    $user->save();
    $name = $user->get('name')->value;
    // Set message and redirect.
    \Drupal::messenger()->addMessage( t('Successfully made @name User Manager.', array('@name' => $name)));
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * @param $uid
   * @param $role
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserRole($uid, $role){
    // Permission must be handled by route.
    // Load user and remove role.
    $current_uid = \Drupal::currentUser()->id();
    if ($uid != $current_uid) {
      $user = User::load($uid);
      $user->removeRole($role);
      $user->save();
      $name = $user->get('name')->value;
      // Set message and redirect.
      \Drupal::messenger()
        ->addMessage(t('Successfully removed @name as User Manager.', ['@name' => $name]));
    } elseif ($uid == $current_uid) {
      \Drupal::messenger()->addWarning(t('You can\'t remove your own roles.'));
    }
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function blockUser($uid){
    // Permission must be handled by route.
    // Block user, also destroys session.
    $current_uid = \Drupal::currentUser()->id();
    if($uid != $current_uid) {
      $account = User::load($uid);
      $account->block();
      $account->save();
      // Get name for message.
      $name = $account->label();
      \Drupal::messenger()->addStatus($name . t(' successfully blocked.'));
    } elseif ($uid == $current_uid) {
      \Drupal::messenger()->addWarning(t('You can\'t block yourself.'));
    }
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
    exit();
  }

  /**
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unblockUser($uid){
    // Permission must be handled by route.
    // Block user, also destroys session.
    $account = User::load($uid);
    $account->activate();
    $account->save();
    // Get name for message.
    $name = $account->label();
    \Drupal::messenger()->addStatus($name . t(' successfully reactivated.'));
    $path = Url::fromRoute('ol_members.all_members_blocked')->toString();
    $response = new RedirectResponse($path);
    $response->send();
    exit();
  }

  /**
   * @param null $gid
   * @param bool $exclude_current_user
   *
   * @return mixed
   */
  public function countMembers($gid = null, $exclude_current_user = false){
    // Get gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid'): $gid;
    // Get data.
    $query = \Drupal::database()->select('ol_group_user', 'lgu');
    $query->addField('lgu', 'id');
    $query->condition('lgu.group_id', $gid);
    $query->condition('ufd.status', 1);
    if($exclude_current_user == true) {
      $query->condition('lgu.member_uid', $this->current_user->id(), '!=');
    }
    $query->join('users_field_data', 'ufd','ufd.uid = lgu.member_uid');
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * @param null $uid
   *
   * @return mixed
   */
  public function isUserManager($uid = null){
    $uid = empty($uid) ? $this->current_user->id() : $uid;
    $user = User::load($uid);
    return $user->hasPermission('administer ol users');
  }

  public function getUserId(){
    return $this->current_user->id();
  }


  /**
   * @param $uid
   *
   * @return mixed
   */
  private function getMemberData($uid){
    $query = \Drupal::database()->select('users_field_data', 'user');
    $query->addField('user', 'name');
    $query->addField('user', 'status');
    $query->addField('user', 'mail');
    $query->addField('user', 'uid');
    $query->condition('user.uid', $uid);
    return $query->execute()->fetchObject();
  }

  private function getGroupAdminUid($gid = null){
    // Get $gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'user_id');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

}
