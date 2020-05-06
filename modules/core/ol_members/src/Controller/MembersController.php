<?php

namespace Drupal\ol_members\Controller;

use Drupal\Core\Controller\ControllerBase;
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
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $form_builder) {
    $this->members = $members;
    $this->form_builder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('form_builder')
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

    // Build it.
    $theme_vars = [
      'members_form' => $members_form,
      'member_cards' => $member_cards,
    ];
    $build = [
      '#theme' => 'members_page',
      '#vars' => $theme_vars,
    ];
    return $build;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getMembersAll(){

    $members_list_data = $this->members->getAllUsers();
    $member_cards = $this->members->renderMembersCards($members_list_data);

    // Build it.
    $theme_vars = [
      'member_cards' => $member_cards,
    ];
    $build = [
      '#theme' => 'members_page',
      '#vars' => $theme_vars,
    ];
    return $build;
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
    $this->members->addUserRole($uid, 'user_manager');
  }

  /**
   * @param $uid
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserManager($uid){
    $this->members->removeUserRole($uid,'user_manager');
  }

  /**
   * @param $uid
   */
  public function blockMember($uid){
    $this->members->blockUser($uid);
  }


}
