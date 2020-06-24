<?php

namespace Drupal\ol_posts\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_comment\Entity\OlComment;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class StreamItemForm.
 */
class PostCommentForm extends FormBase {

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
    return 'post_comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_id = null) {

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id,
      '#required' => true,
    ];
    $form['comment'] = [
      '#prefix' => '<div class="form-row"><div class="col-10">',
      '#type' => 'textfield',
      '#required' => true,
      '#attributes' => array('id' => array('edit-comment-'.$entity_id), 'class' => array('form-control form-control-sm'), 'placeholder' => t('Your comment...'), 'maxlength' => 4000),
      '#suffix' => '</div>',
    ];
    $form['actions'] = [
      '#prefix' => '<div class="col-2">',
      '#type' => 'button',
      '#value' => t('Send',array(), array('context' => 'post_comment')),
      '#attributes' => array('class' => array('btn btn-primary btn-sm')),
      '#suffix' => '</div></div>',
      '#ajax' => [
        'callback' => '::submitCommentAjax',
        'event' => 'click',
        'disable-refocus' => true,
        'progress' => [
          'type' => 'none',
        ]
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
   * Ajax callback to validate the email field.
   */
  public function submitCommentAjax(array &$form, FormStateInterface $form_state) {

    // Initiate.
    $response = new AjaxResponse();
    $comment = Xss::filter($form_state->getValue('comment'));
    $entity_id = Xss::filter($form_state->getValue('entity_id'));
    //$group_id = $this->groups->getCurrentGroupId();
    $user_picture = $this->members->getUserPictureUrl();
    $created = date('H:i' ); // Needed for appending and emitting message.
    $timestamp = time(); // Needed to help determine if screen has to refresh due to missed messages.

    if(strlen($comment) > 0){
      $this->comments->saveComment($comment, $entity_id, 'post', 0); // Create comment item.
      $response->addCommand(new InvokeCommand('#edit-comment-'.$entity_id, 'val', ['']));  // Empty message field.
      // Post message via post_comment, that will handle appending (and emitting to other users via socket.io).
      $response->addCommand(new InvokeCommand(NULL, 'post_comment',
                                              [$comment, $user_picture, $created, $entity_id]));
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
