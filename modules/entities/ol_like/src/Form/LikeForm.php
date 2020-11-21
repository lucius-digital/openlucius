<?php

namespace Drupal\ol_like\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_like\Entity\OlLike;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class LikeForm.
 */
class LikeForm extends FormBase {


  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $language_manager
   */
  protected $groups;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * Class constructor.
   * @param AccountInterface $account
   */
  public function __construct(OlMembers $members, Messenger $messenger, OlGroups $groups, OlComments $comments) {
    $this->members = $members;
    $this->messenger = $messenger;
    $this->groups = $groups;
    $this->comments = $comments;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('messenger'),
      $container->get('olmain.groups'),
      $container->get('olmain.comments')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'like_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = null, $entity_id = null) {

    $uid = \Drupal::currentUser()->id();
    $like_status = $this->getLikeStatus($entity, $entity_id, $uid);
    $like_count = $this->getLikeCount($entity, $entity_id);
    $liked_class = is_numeric($like_status) ? 'liked' : '';
    $button_text = is_numeric($like_status) ? 'Liked' : 'Like';
    $users_that_liked = ($like_count > 0) ? $this->getUsersThatLiked($entity, $entity_id, $uid) : '';

    $form['entity'] = [
      '#type' => 'hidden',
      '#required' => true,
      '#default_value' => $entity,
    ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#required' => true,
      '#default_value' => $entity_id,
    ];
      $form['users_that_liked'] = [
        '#type' => 'markup',
        '#markup' => '<p class="card-text small">
                        <i class="lni lni-heart"></i>
                        <span id="like_count_'.$entity.$entity_id .'" class="badge badge-pill badge-light text-muted like_count">' .$like_count .'</span>
                          <span class="text-muted small">'
                           .$users_that_liked .
                          '</span>
                       </p>',
      ];
    $form['actions'] = [
      '#prefix' => '<span class="'.$entity.$entity_id .' '.$liked_class.'"> ',
      '#type' => 'button',
      '#attributes' => array('class' => array('btn btn-light px-5 btn-sm '.$entity.$entity_id)),
      '#value' => $button_text,
      '#ajax' => [
        'callback' => '::submitLikeAjax',
        'disable-refocus' => true,
        'progress' => [
          'type' => 'none',
          ]
      ],
      '#suffix' => '</span>'
    ];

    $form['#attached']['library'][] = 'ol_like/like';
    return $form;
  }


  /**
   * Ajax callback to validate the email field.
   */
  public function submitLikeAjax(array &$form, FormStateInterface $form_state) {

    // Initiate.
    $response = new AjaxResponse();
    $uid = \Drupal::currentUser()->id();
    $group_id = $this->groups->getCurrentGroupId();
    $entity = $form_state->getValue('entity');
    $entity_id = $form_state->getValue('entity_id');
    $like_count = $this->getLikeCount($entity, $entity_id);
    $like_count = (empty($like_count)) ? 0 : $like_count;
    $like_status = $this->getLikeStatus($entity, $entity_id, $uid);

    // Record exists, unlike, remove record.
    if(is_numeric($like_status)){
      \Drupal::database()->delete('ol_like')
        ->condition('entity', $entity, '=')
        ->condition('entity_id', $entity_id, '=')
        ->condition('user_id', $uid, '=')
        ->condition('group_id', $group_id, '=')
        ->execute();
      // Add ajax commands
      $css_you_liked = ['display' => 'none'];
      $response->addCommand(new CssCommand('.you_liked_' .$entity .$entity_id, $css_you_liked));
      $css = ['color' => '#9f9c9c'];
      $response->addCommand(new CssCommand('.' . $entity.$entity_id, $css));
      $response->addCommand(new InvokeCommand('.' .$entity .$entity_id, 'val', ['Like']));
      $new_count = strval($like_count - 1);
      $response->addCommand(new HtmlCommand('#like_count_'.$entity.$entity_id, $new_count));

    }
    // Record doesn't exists, Like, add record.
    elseif (empty($like_status)) {
      $ol_like = OlLike::create([
        'entity' => Html::escape($entity),
        'entity_id' => $entity_id,
        'group_id' => $group_id,
      ]);
      $ol_like->save();
      // Add ajax commands
      $response->addCommand(new HtmlCommand('.you_liked_'.$entity.$entity_id, 'You, '));
      $css = ['color' => '#007bff'];
      $response->addCommand(new CssCommand('.' .$entity .$entity_id, $css));
      $css_you_liked = ['display' => 'initial'];
      $response->addCommand(new CssCommand('.you_liked_' .$entity .$entity_id, $css_you_liked));
      $response->addCommand(new InvokeCommand('.' .$entity .$entity_id, 'val', ['Liked']));
      $new_count = strval($like_count + 1);
      $response->addCommand(new HtmlCommand('#like_count_'.$entity.$entity_id, $new_count));
    }
    // Wipe all messages, so on page refresh nothing comes up.
    \Drupal::messenger()->deleteAll();
    return $response;
  }

  /**
   * @param $entity
   * @param $entity_id
   * @param $uid
   *
   * @return string
   */
  private function getUsersThatLiked($entity, $entity_id, $uid){
    // Query users that liked.
    $query = \Drupal::database()->select('ol_like', 'oll');
    $query->addField('oll', 'user_id');
    $query->addField('ufd', 'name');
    $query->condition('oll.entity', $entity);
    $query->condition('oll.entity_id', $entity_id);
    $query->join('users_field_data','ufd','ufd.uid = oll.user_id');
    $users = $query->execute()->fetchAll();
    $total = count((array)$users);
    // Build users string
    $users_string = '' ;
    $liked_by_current = '';
    $i = 0;
    foreach ($users as $user){
      if($user->user_id == $uid){
        $liked_by_current = t('You, ');
        continue;
      }
      $users_string .= $user->name;
      if(++$i === $total) {
        $users_string .= '.';
      }else {
        $users_string .= ', ';
      }
    }
    return '<span class="you_liked you_liked_'.$entity.$entity_id.'">'.$liked_by_current .'</span>' .$users_string;
  }

  /**
   * @param $entity
   * @param $entity_id
   * @return mixed
   */
  private function getLikeStatus($entity, $entity_id, $uid){
    $query = \Drupal::database()->select('ol_like', 'll');
    $query->addField('ll', 'id');
    $query->condition('ll.entity', $entity);
    $query->condition('ll.entity_id', $entity_id);
    $query->condition('ll.user_id', $uid);
    return $query->execute()->fetchField();
  }

  /**
   * @param $entity
   * @param $entity_id
   * @return mixed
   */
  private function getLikeCount($entity, $entity_id){
    $query = \Drupal::database()->select('ol_like', 'll');
    $query->addField('ll', 'id');
    $query->condition('ll.entity', $entity);
    $query->condition('ll.entity_id', $entity_id);
    $query->condition('ll.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We need this function, because interface requires it.
    // But nothing is needed here, it's all ajax above.
  }

}
