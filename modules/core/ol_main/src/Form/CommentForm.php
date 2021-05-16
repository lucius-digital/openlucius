<?php

namespace Drupal\ol_main\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class CommentForm.
 */
class CommentForm extends FormBase {

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @param \Drupal\ol_main\Services\OlComments $comments
   * @param \Drupal\ol_main\Services\OlFiles $files
   * @param \Drupal\ol_members\Services\OlMembers $members
   */
  public function __construct(OlComments $comments, OlFiles $files, OlMembers $members, OlGroups $groups) {
    $this->comments = $comments;
    $this->files = $files;
    $this->members = $members;
    $this->groups = $groups;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.comments'),
      $container->get('olmain.files'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null, $entity_type = null, $entity_id = null) {

    // Set defaults.
    $submit_text = t('Submit');
    $body = '';
    $hdd_file_location = $this->files->buildFileLocaton('comment');
    $private_title = array( '1' => t('Only visible for you and questioner.'));
    $private_default = array(0);

    // Get data for edit mode.
    if ($op == 'edit'){
      $comment_data = $this->getCommentData($id);
      $body = $comment_data->body;
      $private_default = array($comment_data->privacy);
      $submit_text = t('Update');
    }

    // Build form.
    $form['comment_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id,
      '#weight' => '0',
    ];
    $form['entity_type'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_type,
      '#weight' => '0',
    ];
    $form['body'] = [
      '#prefix' => '<div class="form-group comment-body">',
      '#type' => 'textarea',
      '#default_value' => $body,
      '#attributes' => array('class' => array('summernote small')),
      '#required' => true,
      '#weight' => '10',
      '#suffix' => '</div>',
    ];
    $form['body_old'] = [
      '#type' => 'textarea',
      '#default_value' => $body,
      '#attributes' => array('class' => array('hidden')),
      '#weight' => '11',
    ];
    // Privacy option only for culture questions for now.
    if($entity_type == 'culture_question') {
      $form['privacy'] = [
        '#prefix' => '<div class="form-group">',
        '#title' => t('Privacy'),
        '#type' => 'checkboxes',
        '#options' => $private_title,
        '#default_value' => $private_default,
        '#weight' => '30',
        '#suffix' => '</div>'
      ];
    }
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-12 col-md-6 small">',
      '#allowed_tags' => ['div'],
      '#weight' => '30',
    ];
    $form['files'] = array(
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#progress_indicator' => 'bar',
      '#progress_message' => t('Please wait...'),
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedFileExtentions(),
      ),
      '#weight' => '40',
    );
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
      '#allowed_tags' => ['div'],
      '#weight' => '45',
    ];
    $form['submit'] = [
      '#prefix' => '<div class="col text-right">',
      '#type' => 'submit',
      '#weight' => '50',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $submit_text,
      '#suffix' => '</div>'
    ];
    // For @-mentions.
    $group_users = $this->members->getUsersNamesInGroupFlatArray();
    $form['#attached']['library'][] = 'ol_main/summernote_inc_init';
    $form['#attached']['drupalSettings']['group_users'] = $group_users;
    // For uploading inline files.
    $group_uuid = $this->groups->getGroupUuidById();
    $form['#attached']['drupalSettings']['group_uuid'] = $group_uuid;
    // Return form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $comment_id = Html::escape($form_state->getValue('comment_id'));
    $reference_type = Html::escape($form_state->getValue('entity_type'));
    $entity_id = Html::escape($form_state->getValue('entity_id'));
    $body = Xss::filter($form_state->getValue('body'), getAllowedHTMLTags() );
    $body = sanatizeSummernoteInput($body);
    $files = $form_state->getValue('files');
    $privacy = $form_state->getValue('privacy')[1];
    // Existing, update comment.
    if(is_numeric($comment_id)){
      $this->comments->updateComment($comment_id, $body, $privacy);
      // Remove inline files that are deleted.
      $body_old = Xss::filter($form_state->getValue('body_old'), getAllowedHTMLTags());
      $this->files->deleteInlineFile($body_old, $body);
    }
    // New, save comment.
    elseif(empty($comment_id)){
      $comment_id = $this->comments->saveComment($body, $entity_id, $reference_type, $privacy);
    }
    // Save new files.
    if(!empty($files)) {
      $this->files->saveFiles($files, 'comment', $comment_id);
    }
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getCommentData($id){
    $query = \Drupal::database()->select('ol_comment', 'olc');
    $query->addField('olc', 'body');
    $query->addField('olc', 'privacy');
    $query->condition('olc.id', $id);
    return $query->execute()->fetchObject();
  }

}
