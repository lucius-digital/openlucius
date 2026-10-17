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
 * Service for managing group operations.
 */
class OlGroups {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * The OL files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  private $files;

  /**
   * Constructs an OlGroups instance.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route match.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The OL files service.
   */
  public function __construct($route, $messenger, $current_user, $renderer, $members, $files) {
    $this->route = $route;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->members = $members;
    $this->files = $files;
  }

  /**
   * Defines group types.
   *
   * @return array
   *   Array of group type definitions.
   */
  public function getGroupTypes() {
    $group_types = [];
    $group_types['company'] = [
      'label' => t('Company wide group'),
      'block_header' => t('Company wide'),
      'icon_class' => t('lni lni-apartment'),
      'weight' => 10,
    ];
    $group_types['team'] = [
      'label' => t('Team'),
      'block_header' => t('Teams'),
      'icon_class' => t('lni lni-users'),
      'weight' => 50,
    ];
    $group_types['project'] = [
      'label' => t('Project'),
      'block_header' => t('Projects'),
      'icon_class' => t('lni lni-rocket'),
      'weight' => 100,
    ];
    // Let other modules alter group types.
    \Drupal::moduleHandler()->invokeAll('alter_group_types', [&$group_types]);
    return $group_types;
  }

  /**
   * Add a new group and optionally add users to it.
   *
   * @param string $name
   *   The group name.
   * @param string $type
   *   The group type.
   * @param int|null $uid
   *   The user ID to add.
   * @param bool $message_redirect
   *   Whether to show a message and redirect.
   * @param string|null $enabled_sections
   *   Comma-separated list of enabled sections.
   * @param int $status
   *   The group status.
   *
   * @return int|string|null
   *   The new group ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addGroup($name, $type = 'team', $uid = NULL, $message_redirect = TRUE, $enabled_sections = NULL, $status = 1) {

    // Get uid if argument is empty.
    $uid = (empty($uid)) ? $this->currentUser->id() : $uid;
    // Create Group.
    // Status = 10, is 'placeholder' group for global / homepage content.
    $ol_group = OlGroup::create(
          [
            'name' => Html::escape($name),
            'type' => $type,
            'landing' => 'stream',
    // @todo jsonize.
            'enabled_sections' => $enabled_sections,
            'user_id' => $uid,
            'status' => $status,
          ]
      );
    $ol_group->save();
    $new_group_id = $ol_group->id();

    // Add all users to this company-wide group.
    if ($type == 'company') {
      // Add all users to this group.
      $all_members = $this->members->getAllUsers();
      foreach ($all_members as $member) {
        $ol_group = OlGroupUser::create(
              [
                'name' => $name,
                'group_id' => $new_group_id,
                'member_uid' => $member->uid,
              ]
          );
        $ol_group->save();
      }
    }
    // Only add current user to this group, since it's not company wide.
    else {
      // Add user to Group.
      $ol_group = OlGroupUser::create(
            [
              'name' => $name,
              'group_id' => $new_group_id,
              'member_uid' => $uid,
            ]
        );
      $ol_group->save();
    }
    if ($message_redirect === TRUE) {
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
   * Get groups for the current user by status.
   *
   * @param int $status
   *   The group status to filter by.
   * @param string|null $type
   *   Optional group type filter.
   *
   * @return array
   *   Array of group data objects.
   */
  public function getGroups($status, $type = NULL) {
    // Get groups data.
    $uid = $this->currentUser->id();
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
   * Get all group IDs the current user belongs to.
   *
   * @return array
   *   Array of group IDs.
   */
  public function getUserGroupsIds() {
    // Get groups data.
    $uid = $this->currentUser->id();
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->addField('lgu', 'member_uid');
    $query->condition('lgu.member_uid', $uid);
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Add activity badge counts to group data.
   *
   * @param array $groups_data
   *   Array of group data objects.
   *
   * @return array
   *   Group data with badge counts added.
   */
  public function addActivityBadge($groups_data) {
    // Loop through groups objects, add badge count if needed.
    foreach ($groups_data as $group_data) {
      // Get current user id.
      $uid = $this->currentUser->id();
      $gid = $group_data->id;
      // Set header image.
      $group_data->header_image_link = $this->getGroupImageLink($gid);

      // Get user count.
      $query = \Drupal::database()->select('ol_group_user', 'ogu');
      $query->addField('ogu', 'id');
      $query->condition('ogu.group_id', $gid);
      $query->condition('ogu.status', 1);
      $group_data->user_count = $query->countQuery()->execute()->fetchField();

      // Get timestamp user last visited group.
      $query = \Drupal::database()->select('ol_group_user', 'ogu');
      $query->addField('ogu', 'changed');
      $query->condition('ogu.group_id', $gid);
      $query->condition('ogu.member_uid', $uid);
      $timestamp_user_group = $query->execute()->fetchField();

      // Get timestamp last stream_item in group.
      $query = \Drupal::database()->select('ol_stream_item', 'osi');
      $query->addField('osi', 'created');
      $query->condition('osi.group_id', $gid);
      $query->orderBy('osi.id', 'desc');
      $timestamp_stream_item = $query->execute()->fetchField();
      // Get new items for this user, if timestamps differ.
      if ($timestamp_stream_item > $timestamp_user_group) {
        // Non-chat.
        $query = \Drupal::database()->select('ol_stream_item', 'osi');
        $query->addField('osi', 'id');
        $query->condition('osi.group_id', $gid);
        $query->condition('osi.created', $timestamp_user_group, '>');
        $query->condition('osi.entity_type', 'chat', '!=');
        $group_data->non_chat_count = $query->countQuery()->execute()->fetchField();
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
   * Get the group UUID by group ID.
   *
   * @param int|null $gid
   *   The group ID.
   *
   * @return string
   *   The group UUID.
   */
  public function getGroupUuidById($gid = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'uuid');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the group ID by UUID.
   *
   * @param string $uuid
   *   The group UUID.
   *
   * @return int
   *   The group ID.
   */
  public function getGroupIdByUuid($uuid) {
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->condition('gr.uuid', $uuid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the current group ID from the route.
   *
   * @return int
   *   The current group ID.
   */
  public function getCurrentGroupId() {
    return $this->route->getParameter('gid');
  }

  /**
   * Get the group name by group ID.
   *
   * @param int|null $gid
   *   The group ID.
   *
   * @return string
   *   The group name.
   */
  public function getGroupName($gid = NULL) {
    // Get current gid if not provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'name');
    $query->condition('gr.id', $gid);
    return html::decodeEntities($query->execute()->fetchField());
  }

  /**
   * Get the group home/landing page.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return string
   *   The group landing page.
   */
  public function getGroupHome($gid) {
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'landing');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Save group settings from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The form state object.
   * @param array|null $options
   *   Array of section options.
   */
  public function saveGroupSettings($form_state = NULL, $options = NULL) {
    // Get form values.
    $name = Html::escape($form_state->getValue('name'));
    $sections = $form_state->getValue('sections');
    $homepage = $form_state->getValue('homepage');
    // $on_top = $form_state->getValue('on_top')[1];
    $status = $form_state->getValue('status')[1];
    // Switch, because checked = 1 means archived, but status 0
    // is archived in dbase.
    $status = ($status == 1) ? 0 : 1;
    // Handle on_top setting.
    // $on_top = ($on_top) ? 99 : 1 ; // 99 = on top | 1 = default.
    // Get current gid.
    $gid = $this->route->getParameter('gid');
    // Build array with only ticked values.
    $enabled_sections_array = [];
    foreach ($sections as $key => $section) {
      if (!empty($section)) {
        $enabled_sections_array[] = $key;
      }
    }
    // Build array with section override names.
    $sections_overrides = [];
    foreach ($options as $key => $section) {
      if (!empty($section)) {
        // Get override name.
        $override = $form_state->getValue('override_' . $key);
        // Build array if override name is provided.
        if (!empty($override)) {
          $sections_overrides[$key] = $override;
        }
      }
    }
    // Encode to json format.
    $sections_overrides_json = json_encode($sections_overrides);

    // Save group settings.
    \Drupal::database()->update('ol_group')
      ->fields(
              [
                'name' => $name,
                'enabled_sections' => implode(',', $enabled_sections_array),
                'section_overrides' => $sections_overrides_json,
                'landing' => $homepage,
              // 'type' => $type,
                'status' => $status,
              ]
          )
      ->condition('id', $gid, '=')
      ->execute();
    \Drupal::messenger()->addStatus(t('Your group settings were saved successfully.'));
  }

  /**
   * Check if the group is set to display on top.
   *
   * @return bool
   *   TRUE if the group is on top.
   */
  public function isOnTop() {
    $gid = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->condition('gr.id', $gid);
    // 99 = 'on top'.
    $query->condition('gr.type', 99);
    return ($query->execute()->fetchField()) ? TRUE : FALSE;
  }

  /**
   * Get the archived status of the current group.
   *
   * @return mixed
   *   The group status.
   */
  public function isArchived() {
    $gid = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'status');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the group type.
   *
   * @param int|null $gid
   *   The group ID.
   *
   * @return string
   *   The group type.
   */
  public function getGroupType($gid = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'type');
    $query->condition('gr.id', $gid);
    return $query->execute()->fetchField();
  }

  /**
   * Redirect to the top group (disabled).
   */
  public function disabledRedirectToTopGroup() {
    // Get current uid.
    $uid = $this->currentUser->id();
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('gr', 'id');
    $query->addField('gr', 'landing');
    // $query->condition('gr.type', 99); // 99 = 'on top'.
    $query->condition('lgu.member_uid', $uid);
    $query->condition('gr.status', 1);
    // $query->condition('gr.type', 'company');
    // $query->orderBy('gr.id', 'desc');
    $query->orderBy('gr.name', 'asc');
    $query->join('ol_group_user', 'lgu', 'lgu.group_id = gr.id');
    $query->range(0, 1);
    $top_gid = $query->execute()->fetchObject();
    // Get landing page of top group.
    // Nasty, but else fromRoute would be too dynamic to build.
    global $base_url;
    $path = $base_url . '/group/' . $top_gid->id . '/' . $top_gid->landing;
    $response = new TrustedRedirectResponse($path);
    $response->send();
    // Stop here, needed to prevent Notice message.
    exit;
  }

  /**
   * Get the header image file ID for a group.
   *
   * @param int|null $gid
   *   The group ID.
   *
   * @return int
   *   The file ID of the header image.
   */
  public function getHeaderImage($gid = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->condition('olf.group_id', $gid);
    $query->condition('olf.entity_type', 'group_header');
    $query->condition('olf.status', 1);
    return $query->execute()->fetchField();
  }

  /**
   * Get the group image URL.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return string|null
   *   The image URL or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupImageLink($gid) {
    // @todo de-duplicate, also in OlStream Service
    $header_fid = $this->getHeaderImage($gid);
    if (!empty($header_fid)) {
      $header_uri = $this->files->getFileUri($header_fid);
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('50x50');
      return $style->buildUrl($header_uri);
    }
  }

  /**
   * Render HTML cards for archived groups.
   *
   * @param array $groups_data
   *   Array of group data objects.
   *
   * @return string
   *   Rendered HTML for archived group cards.
   */
  public function renderArchivedGroupsCards($groups_data) {

    // Initiate html var.
    $groups_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($groups_data as $group) {
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
   * Get the name of the group admin.
   *
   * @param int $gid
   *   The group ID.
   * @param int $uid
   *   The user ID.
   *
   * @return string
   *   The admin user name.
   */
  public function getGroupAdminName($gid, $uid) {
    $query = \Drupal::database()->select('ol_group', 'gr');
    $query->addField('ufd', 'name');
    $query->condition('gr.id', $gid);
    $query->join('users_field_data', 'ufd', 'ufd.uid = gr.user_id');
    return $query->execute()->fetchField();
  }

  /**
   * Initiate the global group for a new user.
   *
   * @param int $id_new_user
   *   The new user ID.
   *
   * @return int|string|null
   *   The global group ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function initiateGlobalGroup($id_new_user) {
    // Add new 'global group' for home posts.
    $global_group_id = $this->addGroup('home_', 'global_group', $id_new_user, FALSE, '', 10);
    // Save new group id to general settings.
    // Create settings entity.
    $entity = OlGeneralSettings::create(
          [
            'global_group_id' => $global_group_id,
            'user_id' => $id_new_user,
          ]
      );
    $entity->save();
    // Return group_id.
    return $global_group_id;
  }

  /**
   * Add a user to the global group.
   *
   * @param int $uid
   *   The user ID to add.
   */
  public function addUserToGlobalGroup($uid) {
    // Get global group id.
    $global_group_id = $this->getGlobalGroupId();
    // Add user to global group.
    $this->members->addUserToGroup($uid, $global_group_id, 'global_group');
  }

  /**
   * Get the global group ID.
   *
   * @return int
   *   The global group ID.
   */
  public function getGlobalGroupId() {
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'global_group_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    return $query->execute()->fetchField();
  }

  /**
   * Update the home tabs positions.
   *
   * @param array $ordered_items
   *   Array of ordered tab items.
   *
   * @return bool
   *   TRUE if update was successful.
   */
  public function updateHomeTabsPositions($ordered_items) {
    // Encode for database.
    $json_items = json_encode($ordered_items);
    // For security hardening.
    $group_id = $this->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if ($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields(
                [
                  'tabs' => $json_items,
                ]
            )
        ->condition('global_group_id', $group_id)
        ->execute();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the home tabs positions.
   *
   * @return array
   *   Array of tab positions.
   */
  public function getHomeTabsPositions() {
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'tabs');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    if ($tabs = $query->execute()->fetchField()) {
      return json_decode($tabs, TRUE);
    }
    // @todo , proper error handling.
    return [];
  }

}
