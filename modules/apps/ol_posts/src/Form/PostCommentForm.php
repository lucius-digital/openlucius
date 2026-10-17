<?php

namespace Drupal\ol_posts\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding comments to posts.
 */
class PostCommentForm extends FormBase {

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * PostCommentForm constructor.
   *
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
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
    return 'post_comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_id = NULL) {

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id,
      '#required' => TRUE,
    ];
    $form['comment'] = [
      '#prefix' => '<div class="form-row"><div class="col-10">',
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'id' => ['edit-comment-' . $entity_id],
        'class' => [
          'form-control',
          'form-control-sm',
          'mention-suggest',
        ],
        'placeholder' => t('Your comment...'),
        'maxlength' => 4000,
      ],
      '#suffix' => '</div>',
    ];
    $form['actions'] = [
      '#prefix' => '<div class="col-2">',
      '#type' => 'button',
      '#value' => t('Send', [], ['context' => 'post_comment']),
      '#attributes' => ['class' => ['btn btn-primary btn-sm']],
      '#suffix' => '</div></div>',
      '#ajax' => [
        'callback' => '::submitCommentAjax',
        'event' => 'click',
        'disable-refocus' => TRUE,
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['#attached']['library'][] = 'ol_main/ol_comments';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Ajax callback to submit a comment.
   */
  public function submitCommentAjax(array &$form, FormStateInterface $form_state) {

    // Initiate.
    $response = new AjaxResponse();
    $comment = Html::escape($form_state->getValue('comment'));
    $entity_id = Html::escape($form_state->getValue('entity_id'));
    $user_picture = $this->members->getUserPictureUrl();
    // Needed for appending and emitting message.
    $created = date('H:i');
    // Needed to help determine if screen has to refresh due to missed messages.
    $timestamp = time();

    if (strlen($comment) > 0) {
      // Create comment item.
      $this->comments->saveComment($comment, $entity_id, 'post', 0);
      // Empty message field.
      $response->addCommand(new InvokeCommand('#edit-comment-' . $entity_id, 'val', ['']));
      // Post message via post_comment, that will handle appending
      // (and emitting to other users via socket.io).
      $response->addCommand(new InvokeCommand(NULL, 'post_comment',
        [$comment, $user_picture, $created, $entity_id]
      ));
    }
    // Wipe all messages, so on page refresh nothing comes up.
    $this->messenger->deleteAll();
    // Return response.
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We need this function, because interface requires it.
    // But nothing is needed here, it's all ajax above.
  }

}
