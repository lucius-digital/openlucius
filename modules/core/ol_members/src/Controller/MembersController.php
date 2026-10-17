<?php

namespace Drupal\ol_members\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Form\MembersForm;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for managing group members.
 */
class MembersController extends ControllerBase {

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Sections service.
   *
   * @var \Drupal\ol_main\Services\OlSections
   */
  protected $sections;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $formBuilder, OlSections $sections) {
    $this->members = $members;
    $this->formBuilder = $formBuilder;
    $this->sections = $sections;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('form_builder'),
      $container->get('olmain.sections')
    );
  }

  /**
   * Render the members page for a specific group.
   *
   * @param mixed $gid
   *   The group identifier.
   *
   * @return array
   *   Render array for the members page.
   *
   * @throws \Exception
   */
  public function getMembersGroup($gid) {
    $group_admin_uid = $this->members->isGroupAdmin();
    $members_form = $this->formBuilder->getForm(MembersForm::class);
    $members_list_data = $this->members->getUsersInGroup();
    $member_cards = $this->members->renderMembersCards($members_list_data, $gid, $group_admin_uid);
    $is_user_manager = $this->members->isUserManager();
    $can_add_members = is_numeric($group_admin_uid) || $is_user_manager;
    $page_title = $this->sections->getSectionOverrideTitle('members', 'Members');

    // Build it.
    $theme_vars = [
      'members_form' => $members_form,
      'member_cards' => $member_cards,
      'can_add_members' => $can_add_members,
      'page_title' => $page_title,
    ];
    return [
      '#theme' => 'members_page',
      '#vars' => $theme_vars,
    ];
  }

  /**
   * Render the all members page.
   *
   * @return array
   *   Render array for the all members page.
   *
   * @throws \Exception
   */
  public function getMembersAll() {
    // Get data.
    $members_list_data = $this->members->getAllUsers();
    $member_cards = $this->members->renderMembersCards($members_list_data);
    $title = t('Active users');
    // Build it.
    $theme_vars = [
      'member_cards' => $member_cards,
      'title' => $title,
      'all_members' => 1,
    ];
    return [
      '#theme' => 'members_page',
      '#vars' => $theme_vars,
    ];
  }

  /**
   * Render the blocked members page.
   *
   * @return array
   *   Render array for the blocked members page.
   *
   * @throws \Exception
   */
  public function getMembersAllBlocked() {
    // Get data.
    $members_list_data = $this->members->getAllUsers(0);
    $member_cards = $this->members->renderMembersCards($members_list_data, NULL, NULL, TRUE);
    $title = t('Blocked users');
    // Build it.
    $theme_vars = [
      'member_cards' => $member_cards,
      'title' => $title,
      'blocked_users' => 1,
    ];
    return [
      '#theme' => 'members_page',
      '#vars' => $theme_vars,
    ];
  }

  /**
   * Remove a member from a group.
   *
   * @param mixed $gid
   *   The group identifier.
   * @param mixed $uid
   *   The user identifier.
   */
  public function removeMemberFromGroup($gid, $uid) {
    $this->members->deleteUserGroupRelation($uid);
  }

  /**
   * Make a user a manager.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function makeUserManager($uid) {
    $this->members->addUserRole($uid, 'manager');
  }

  /**
   * Remove manager role from a user.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserManager($uid) {
    $this->members->removeUserRole($uid, 'manager');
  }

  /**
   * Block a member user.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function blockMember($uid) {
    $this->members->blockUser($uid);
  }

  /**
   * Unblock a member user.
   *
   * @param mixed $uid
   *   The user identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unblockMember($uid) {
    $this->members->unblockUser($uid);
  }

}
