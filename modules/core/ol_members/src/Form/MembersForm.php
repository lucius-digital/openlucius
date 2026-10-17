<?php

namespace Drupal\ol_members\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing group members.
 */
class MembersForm extends FormBase {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Stream service.
   *
   * @var \Drupal\ol_stream\Services\OlStream
   */
  protected $stream;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * MembersForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRoute
   *   Current route match.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   */
  public function __construct(AccountInterface $account, OlMembers $members, CurrentRouteMatch $currentRoute, Messenger $messenger, LanguageManager $languageManager, OlStream $stream, OlGroups $groups) {
    $this->account = $account;
    $this->members = $members;
    $this->currentRoute = $currentRoute;
    $this->messenger = $messenger;
    $this->languageManager = $languageManager;
    $this->stream = $stream;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('olmembers.members'),
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('olstream.stream'),
      $container->get('olmain.groups')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'members_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get current group id.
    $group_id = $this->currentRoute->getParameter('gid');
    // Handle new members permission.
    $disabled = TRUE;
    $placeholder = t('(Disabled: only Managers can add new members)');
    if ($this->account->hasPermission('administer ol users')) {
      $disabled = FALSE;
      $placeholder = t('Email address of new user...');
    }

    // Build form.
    $form['group_id'] = [
      '#type' => 'hidden',
      '#default_value' => $group_id,
      '#weight' => '0',
    ];
    $form['uid'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '0',
      '#title' => t('Choose an existing user from within your organization:'),
      '#default_value' => '0',
      '#options' => $this->getUsersNotInCurrentGroup(),
      '#attributes' => ['class' => ['form-control']],
      '#suffix' => '</div>',
    ];
    $form['new_member_email'] = [
      '#prefix' => '<div class="form-row"><div class="col-6">',
      '#type' => 'email',
      '#title' => t('Or, add a new user:'),
      '#weight' => '0',
      '#label' => 'test',
      '#disabled' => $disabled,
      '#attributes' => ['placeholder' => $placeholder, 'class' => ['form-control']],
      '#suffix' => '</div>',
    ];
    $form['role'] = [
      '#prefix' => '<div class="col-6">',
      '#type' => 'select',
      '#weight' => '0',
      '#title' => t('Choose role for new user:'),
      '#default_value' => '0',
      '#options' => $this->getRoleOptions(),
      '#disabled' => $disabled,
      '#attributes' => ['class' => ['form-control']],
      '#suffix' => '</div></div>',
    ];
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => ['class' => ['btn btn-success']],
      '#value' => $this->t('Add member'),
      '#suffix' => '</div>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $uid = $form_state->getValue('uid');
    $group_id = $form_state->getValue('group_id');
    $email = $form_state->getValue('new_member_email');
    $role = $form_state->getValue('role');
    // Check if 'new' email already exists.
    $email_exists = $this->checkEmailExistence($email);
    // Get current language code.
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Create new user, if it doesn't exist already.
    if (!empty($email) && $role && $email_exists == FALSE && $this->account->hasPermission('administer ol users')) {
      // Create new user.
      $account = $this->members->addNewUser($email, $language, $role);
      $account_id = $account->id();
      // Get group type.
      $group_type = $this->groups->getGroupType();
      // If group type is 'company', add new user to all company-wide groups.
      if ($group_type == 'company') {
        $this->members->addUserToCompanyWideGroups($account_id);
        // If type is not 'company', add only to this group.
      }
      else {
        $this->members->addUserToGroup($account_id, $group_id);
      }
      // Add new user to global group.
      if ($role == 'manager' || $role == 'standard_user') {
        $this->groups->addUserToGlobalGroup($account_id);
      }
      // Send login email to new user.
      _user_mail_notify('register_no_approval_required', $account);
      // Create new stream item.
      $this->stream->addStreamItem($group_id, 'user_added', $email, 'user', $account_id);
      // Add message.
      $this->messenger->addStatus(
        t('@mail added as a member. A login link was sent by e-mail.', ['@mail' => $email])
      );
    }
    // Add existing user to group.
    elseif (!empty($uid)) {
      $this->members->addUserToGroup($uid, $group_id);
      $username = $this->members->getUserName($uid);
      // Create stream item.
      $this->stream->addStreamItem($group_id, 'user_added', $username, 'user', $uid);
      $this->messenger->addStatus(t('Member successfully added to this group'));
    }
  }

  /**
   * Check if an email already exists.
   *
   * @param string $email
   *   The email address to check.
   *
   * @return bool
   *   TRUE if email exists, FALSE otherwise.
   */
  private function checkEmailExistence($email) {
    // Check if email already exists.
    $user = user_load_by_mail($email);
    if ($user == TRUE) {
      $this->messenger->addError(t('@email was not added, because it already exists.', ['@email' => $email]));
    }
    return !empty($user);
  }

  /**
   * Helper function for populating user drop down.
   */
  private function getUsersNotInCurrentGroup() {
    // Get all members in current group.
    $current_members = $this->members->getUsersInGroup();
    $group_members = [];
    foreach ($current_members as $member) {
      array_push($group_members, $member->uid);
    }
    $query = \Drupal::database()->select('users_field_data', 'ufd');
    $query->addField('ufd', 'name');
    $query->addField('ufd', 'uid');
    $query->condition('ufd.uid', $group_members, 'NOT IN');
    $query->condition('ufd.status', 1);
    $query->addTag('ol_user_list');
    $users = $query->execute()->fetchAll();

    // Build and return the option list.
    $user_list = ['0' => '- ' . t('Select user') . ' -'];
    foreach ($users as $user) {
      $user_list[$user->uid] = $user->name;
    }
    return $user_list;
  }

  /**
   * Helper function for populating role drop down.
   */
  private function getRoleOptions() {
    // Get all roles and return options.
    $roles = $this->members->getRoles();
    $roles_options = [];
    foreach ($roles as $key => $role) {
      $roles_options[$key] = $role;
    }
    return $roles_options;
  }

}
