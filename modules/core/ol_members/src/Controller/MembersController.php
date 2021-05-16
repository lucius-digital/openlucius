<?php

namespace Drupal\ol_members\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;

/**
 * Class MembersController.
 */
class MembersController extends ControllerBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $sections
   */
  protected $sections;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $form_builder, OlSections $sections) {
    $this->members = $members;
    $this->form_builder = $form_builder;
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
   * @param $gid
   * @return array
   * @throws \Exception
   */
  public function getMembersGroup($gid){

    $group_admin_uid = $this->members->isGroupAdmin();
    $members_form = $this->form_builder->getForm(\Drupal\ol_members\Form\MembersForm::class);
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
   * @return array
   * @throws \Exception
   */
  public function getMembersAll(){
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
   * @return array
   * @throws \Exception
   */
  public function getMembersAllBlocked(){
    // Get data.
    $members_list_data = $this->members->getAllUsers(0);
    $member_cards = $this->members->renderMembersCards($members_list_data, null, null, TRUE);
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
   * @param $gid
   * @param $uid
   */
  public function removeMemberFromGroup($gid, $uid){
    $this->members->deleteUserGroupRelation($uid);
  }

  /**
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function makeUserManager($uid) {
    $this->members->addUserRole($uid, 'manager');
  }

  /**
   * @param $uid
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserManager($uid){
    $this->members->removeUserRole($uid,'manager');
  }

  /**
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function blockMember($uid){
    $this->members->blockUser($uid);
  }

  /**
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unblockMember($uid){
    $this->members->unblockUser($uid);
  }


}
