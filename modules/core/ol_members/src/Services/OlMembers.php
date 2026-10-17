<?php

namespace Drupal\ol_members\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\ol_group_user\Entity\OlGroupUser;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service for managing organization members.
 */
class OlMembers {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\entity_type_managerInterface
   */
  protected $entity_type_manager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * OlMembers constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   Current route match.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   * @param \Drupal\Core\Entity\entity_type_managerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service.
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
   * Helper function, provide roles to work with.
   */
  public function getRoles() {
    return [
      'standard_user' => t('Standard User'),
      'manager' => t('Manager'),
      'external_user' => t('External User'),
    ];
  }

  /**
   * Render member cards HTML.
   *
   * @param array $members_list_data
   *   Array of member data objects.
   * @param mixed $group_id
   *   The group identifier.
   * @param mixed $group_admin_uid
   *   The group admin user ID.
   * @param bool $blocked_users
   *   Whether these are blocked users.
   *
   * @return string
   *   Rendered HTML string for member cards.
   *
   * @throws \Exception
   */
  public function renderMembersCards($members_list_data, $group_id = NULL, $group_admin_uid = NULL, $blocked_users = NULL) {
    // Initiate html var.
    $members_html = '';
    // Loop through array and render HTML rows via twig file.
    // Needed to show options drop down.
    $members_row_data['current_is_group_admin'] = (is_numeric($group_admin_uid)) ? TRUE : FALSE;
    $current_user = User::load(\Drupal::currentUser()->id());
    $is_user_manager = $current_user->hasPermission('administer ol users');

    foreach ($members_list_data as $member) {
      // Needed to show drop down item.
      $members_row_data['current_is_user_admin'] = $is_user_manager == TRUE;
      // Needed for 'group admin' badge.
      $members_row_data['is_group_admin'] = $member->uid == $this->getGroupAdminUid();
      $members_row_data['user_picture'] = $this->getUserPictureUrl($member->uid);
      $user = User::load($member->uid);
      $roles = $user->getRoles();
      $members_row_data['role'] = (in_array('external_user', $roles)) ? 'External User' : '';
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
   * Get drop down options for group members.
   *
   * @return array
   *   Array of member options keyed by email.
   */
  public function getGroupMembersDropDown() {
    // Get members.
    $members = $this->getUsersInGroup();
    // Build and return options.
    $options = [];
    foreach ($members as $member) {
      $label = $member->name;
      $key = $member->mail;
      $options[$key] = $label;
    }
    return $options;
  }

  /**
   * Get users in the current group.
   *
   * @param bool $exclude_current_uid
   *   Whether to exclude the current user.
   * @param mixed $gid
   *   The group identifier.
   *
   * @return mixed
   *   Array of user objects in the group.
   */
  public function getUsersInGroup($exclude_current_uid = FALSE, $gid = NULL) {
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
    if ($exclude_current_uid == TRUE) {
      $query->condition('ufd.uid', $this->current_user->id(), '!=');
    }
    $query->join('users_field_data', 'ufd', 'ufd.uid = gu.member_uid');
    $query->orderBy('gu.name', 'asc');
    return $query->execute()->fetchAll();
  }

  /**
   * Get flat array of usernames in group.
   *
   * @param bool $exclude_current_uid
   *   Whether to exclude the current user.
   * @param mixed $gid
   *   The group identifier.
   *
   * @return array
   *   Flat array of usernames.
   */
  public function getUsersNamesInGroupFlatArray($exclude_current_uid = FALSE, $gid = NULL) {
    // Get current group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Get and return data.
    $query = $this->database->select('ol_group_user', 'gu');
    $query->addField('ufd', 'name');
    $query->condition('gu.group_id', $gid);
    $query->condition('gu.status', 1);
    $query->condition('ufd.status', 1);
    if ($exclude_current_uid == TRUE) {
      $query->condition('ufd.uid', $this->current_user->id(), '!=');
    }
    $query->join('users_field_data', 'ufd', 'ufd.uid = gu.member_uid');
    $query->orderBy('gu.name', 'asc');
    $usernames = $query->execute()->fetchAll();
    // Build and return flat array with usernames.
    // Start with 'Group' for sending mentions to whole group.
    $usernames_array[] = 'Group';
    foreach ($usernames as $username) {
      $usernames_array[] = $username->name;
    }
    return $usernames_array;
  }

  /**
   * Get all users with optional status filter.
   *
   * @param int $status
   *   User status (1 = active, 0 = blocked).
   *
   * @return mixed
   *   Array of user objects.
   */
  public function getAllUsers($status = 1) {
    // Get current user roles.
    // Get and return data.
    $query = $this->database->select('users_field_data', 'ufd');
    $query->addField('ufd', 'uid');
    $query->condition('ufd.status', $status);
    $query->orderBy('ufd.name', 'asc');
    $query->addTag('ol_user_list');
    // Check if user is a collaborator, then only show users of shared groups.
    return $query->execute()->fetchAll();
  }

  /**
   * Delete a user-group relation.
   *
   * @param mixed $uid
   *   The user identifier.
   */
  public function deleteUserGroupRelation($uid) {
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Check if uid is group admin, that can't be removed.
    $group_admin_uid = $this->isGroupAdmin($uid);
    // Only remove if it's not group admin.
    if ($group_admin_uid == NULL) {
      // Get username for stream item.
      $user_name = $this->getUserName($uid);
      // Remove user from group.
      $this->database->delete('ol_group_user')
        ->condition('group_id', $gid)
        ->condition('member_uid', $uid)
        ->execute();
      // Message and redirect.
      $this->messenger->addStatus(t('Member successfully removed from this group'));
      // We can't have this as dependency, else install profile will fail.
      // So for now, procedural use of this service.
      $stream = \Drupal::service('olstream.stream');
      $stream->addStreamItem($gid, 'user_removed', $user_name, 'user', $uid);
    }
    if (is_numeric($group_admin_uid)) {
      \Drupal::messenger()->addWarning(t("You can't remove group administrator from a group"));
    }
    $path = Url::fromRoute('ol_members.group_members', ['gid' => $gid])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * Delete all user-group relations for a user.
   *
   * @param mixed $uid
   *   The user identifier.
   */
  public function deleteAllUserGroupRelations($uid) {
    // Get group ids of user that is blocked.
    $gids = getUserGroups($uid);
    // Get username for stream item.
    $user_name = $this->getUserName($uid);
    // Loop through groups and remove user.
    // Don't remove user from global_group.
    foreach ($gids as $gid) {
      // Remove user from group.
      $this->database->delete('ol_group_user')
        ->condition('group_id', $gid)
        ->condition('member_uid', $uid)
        ->condition('name', 'global_group', '!=')
        ->execute();
      // Add stream item per group.
      $stream = \Drupal::service('olstream.stream');
      $stream->addStreamItem($gid, 'user_removed', $user_name, 'user', $uid);
    }
  }

  /**
   * Check if a user is a group admin.
   *
   * @param mixed $user_id
   *   The user identifier (optional, uses current user if not provided).
   *
   * @return int|null
   *   The group admin user ID if user is admin, NULL otherwise.
   */
  public function isGroupAdmin($user_id = NULL) {
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Get current user data if none provided.
    $user_id = (empty($user_id)) ? $this->current_user->id() : $user_id;
    // Check if current user is group admin.
    $query = $this->database->select('ol_group', 'gr');
    $query->addField('gr', 'user_id');
    $query->condition('gr.user_id', $user_id);
    $query->condition('gr.id', $gid);
    $group_admin_uid = $query->execute()->fetchField();
    // Return group_admin_uid if user is group admin, else return NULL.
    return (is_numeric($group_admin_uid) && !empty($group_admin_uid)) ? $group_admin_uid : NULL;
  }

  /**
   * Get the user picture URL.
   *
   * @param mixed $uid
   *   The user identifier (optional, uses current user if not provided).
   *
   * @return string
   *   The URL of the user's picture.
   */
  public function getUserPictureUrl($uid = NULL) {
    // Get uid if not provided.
    $uid = empty($uid) ? $this->current_user->id() : $uid;
    // Query user picture file uri.
    $query = $this->database->select('user__field_user_picture', 'ufp');
    $query->addField('fileman', 'uri');
    $query->condition('ufp.entity_id', $uid);
    $query->join('file_managed', 'fileman', 'fileman.fid = ufp.field_user_picture_target_id');
    $file_uri = $query->execute()->fetchField();

    // Style image if it exists.
    if (!empty($file_uri)) {
      $style = $this->entity_type_manager->getStorage('image_style')->load('50x50');
      $picture_url = $style->buildUrl($file_uri);
    }
    else {
      // Default if user picture doesn't exist.
      global $base_url;
      $theme = \Drupal::theme()->getActiveTheme();
      $picture_url = $base_url . '/' . $theme->getPath() . '/images/default_user.jpg';
    }
    return $picture_url;
  }

  /**
   * Get a user's name by uid.
   *
   * @param mixed $uid
   *   The user identifier (optional, uses current user if not provided).
   *
   * @return string
   *   The user's account name.
   */
  public function getUserName($uid = NULL) {
    if (empty($uid)) {
      return $this->current_user->getAccountName();
    }
    else {
      $account = User::load($uid);
      return $account->getAccountName();
    }
  }

  /**
   * Create a new user account.
   *
   * @param string $email
   *   User's email address.
   * @param string $language
   *   User's language code.
   * @param string $role
   *   Role to assign to the user.
   * @param string $name
   *   Optional display name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created user entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addNewUser($email, $language, $role, $name = NULL) {
    $name = ($name) ? $name : $email;
    $password = \Drupal::service('password_generator')->generate();
    $account = User::create();
    $account->enforceIsNew();
    $account->setPassword($password);
    $account->setEmail($email);
    $account->setUsername($name);
    $account->addRole($role);
    $account->set('langcode', $language);
    $account->set('preferred_langcode', $language);
    $account->activate();
    $account->save();
    return $account;
  }

  /**
   * Add a user to a specific group.
   *
   * @param mixed $uid
   *   The user identifier.
   * @param mixed $gid
   *   The group identifier.
   * @param string $name
   *   Optional name for the relation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserToGroup($uid, $gid, $name = NULL) {
    // Facilitate $name.
    $name = ($name) ? $name : 'uid: ' . $uid . ' group id: ' . $gid;
    // Create new relation.
    $ol_group_user = OlGroupUser::create([
      'name' => $name,
      'member_uid' => Html::escape($uid),
      'group_id' => $gid,
    ]);
    $ol_group_user->save();
  }

  /**
   * Add a user to all company-wide groups.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserToCompanyWideGroups($uid) {
    // Get all company-wide groups.
    $group_service = \Drupal::service('olmain.groups');
    $company_groups = $group_service->getGroups(1, 'company');
    // Loop through company-wide groups and add user.
    foreach ($company_groups as $group) {
      // Create new relation.
      $ol_group_user = OlGroupUser::create([
        'name' => 'uid: ' . $uid . ' group id: ' . $group->id,
        'member_uid' => Html::escape($uid),
        'group_id' => $group->id,
      ]);
      $ol_group_user->save();
    }
  }

  /**
   * Add a role to a user.
   *
   * @param mixed $uid
   *   The user identifier.
   * @param string $role
   *   The role to add.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserRole($uid, $role) {
    // Permission must be handled by route.
    // Load user and set role.
    $user = User::load($uid);
    $user->addRole($role);
    $user->save();
    $name = $user->get('name')->value;
    // Set message and redirect.
    \Drupal::messenger()->addMessage(t('Successfully added role Manager to @name.', ['@name' => $name]));
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * Remove a role from a user.
   *
   * @param mixed $uid
   *   The user identifier.
   * @param string $role
   *   The role to remove.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserRole($uid, $role) {
    // Permission must be handled by route.
    // Load user and remove role.
    $current_uid = \Drupal::current_user()->id();
    if ($uid != $current_uid) {
      $user = User::load($uid);
      $user->removeRole($role);
      $user->save();
      $name = $user->get('name')->value;
      // Set message and redirect.
      \Drupal::messenger()
        ->addMessage(t('Successfully removed Manager role from @name.', ['@name' => $name]));
    }
    elseif ($uid == $current_uid) {
      \Drupal::messenger()->addWarning(t("You can't remove your own roles."));
    }
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * Block a user account.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function blockUser($uid) {
    // Permission must be handled by route.
    // Block user, also destroys session.
    $current_uid = \Drupal::current_user()->id();
    if ($uid != $current_uid) {
      $account = User::load($uid);
      $account->block();
      $account->save();
      // Remove all group relations.
      $this->deleteAllUserGroupRelations($uid);
      // Get name for message.
      $name = $account->label();
      \Drupal::messenger()->addStatus(t('@name successfully blocked.', ['@name' => $name]));
    }
    elseif ($uid == $current_uid) {
      \Drupal::messenger()->addWarning(t("You can't block yourself."));
    }
    $this->deleteAllUserGroupRelations($uid);
    $path = Url::fromRoute('ol_members.all_members')->toString();
    $response = new RedirectResponse($path);
    $response->send();
    exit();
  }

  /**
   * Unblock a user account.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unblockUser($uid) {
    // Permission must be handled by route.
    // Unblock user.
    $account = User::load($uid);
    $account->activate();
    $account->save();
    // Get name for message.
    $name = $account->label();
    \Drupal::messenger()->addStatus(t('@name successfully reactivated.', ['@name' => $name]));
    $path = Url::fromRoute('ol_members.all_members_blocked')->toString();
    $response = new RedirectResponse($path);
    $response->send();
    exit();
  }

  /**
   * Count members in a group.
   *
   * @param mixed $gid
   *   The group identifier (optional, uses current route if not provided).
   * @param bool $excludecurrent_user
   *   Whether to exclude the current user from count.
   *
   * @return int
   *   Number of members in the group.
   */
  public function countMembers($gid = NULL, $excludecurrent_user = FALSE) {
    // Get gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Get data.
    $query = \Drupal::database()->select('ol_group_user', 'lgu');
    $query->addField('lgu', 'id');
    $query->condition('lgu.group_id', $gid);
    $query->condition('ufd.status', 1);
    if ($excludecurrent_user == TRUE) {
      $query->condition('lgu.member_uid', $this->current_user->id(), '!=');
    }
    $query->join('users_field_data', 'ufd', 'ufd.uid = lgu.member_uid');
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Check if a user is a manager.
   *
   * @param mixed $uid
   *   The user identifier (optional, uses current user if not provided).
   *
   * @return bool
   *   TRUE if the user is a manager.
   */
  public function isUserManager($uid = NULL) {
    $uid = empty($uid) ? $this->current_user->id() : $uid;
    $user = User::load($uid);
    return $user->hasPermission('administer ol users');
  }

  /**
   * Get the current user ID.
   *
   * @return int
   *   The current user ID.
   */
  public function getUserId() {
    return $this->current_user->id();
  }

  /**
   * Check if a user is in a group.
   *
   * @param mixed $gid
   *   The group identifier.
   * @param mixed $uid
   *   The user identifier (optional, uses current user if not provided).
   *
   * @return bool
   *   TRUE if the user is in the group.
   */
  public function checkUserInGroup($gid, $uid = NULL) {
    $uid = empty($uid) ? $this->current_user->id() : $uid;
    $query = \Drupal::database()->select('ol_group_user', 'lgu');
    $query->addField('lgu', 'id');
    $query->condition('lgu.member_uid', $uid);
    $query->condition('lgu.group_id', $gid);
    return ($query->execute()->fetchField()) ? TRUE : FALSE;
  }

  /**
   * Get member data by uid.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @return object
   *   Object containing member data.
   */
  private function getMemberData($uid) {
    $query = \Drupal::database()->select('users_field_data', 'user');
    $query->addField('user', 'name');
    $query->addField('user', 'status');
    $query->addField('user', 'mail');
    $query->addField('user', 'uid');
    $query->condition('user.uid', $uid);
    return $query->execute()->fetchObject();
  }

  /**
   * Get the group admin user ID.
   *
   * @param mixed $gid
   *   The group identifier (optional, uses current route if not provided).
   *
   * @return mixed
   *   The user ID of the group admin.
   */
  private function getGroupAdminUid($gid = NULL) {
    // Get $gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'user_id');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

}
