<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlFiles;
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
   * @param \Drupal\ol_main\Services\OlComments $comments
   */
  public function __construct(OlComments $comments, OlFiles $files) {
    $this->comments = $comments;
    $this->files = $files;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.comments'),
      $container->get('olmain.files')
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

    // Get data for edit mode.
    if ($op == 'edit'){
      $comment_data = $this->getCommentBody($id);
      $body = $comment_data->body;
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
      '#type' => 'text_format',
      '#format' => 'ol_rich_text',
      '#default_value' => $body,
      '#required' => true,
      '#weight' => '10',
      '#suffix' => '</div>',
    ];
    $form['files'] = array(
      '#prefix' => '<div class="form-group message-body">',
      '#title' => t('Attach files'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedFileExtentions(),
      ),
      '#suffix' => '</div>',
      '#weight' => '30',
    );
    $form['submit'] = [
      '#prefix' => '<div class="form-group text-right">',
      '#type' => 'submit',
      '#weight' => '30',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $submit_text,
      '#suffix' => '</div>'
    ];
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
    $body = $form_state->getValue('body')['value'];
    $body = check_markup($body,'ol_rich_text');
    $files = $form_state->getValue('files');
    // Existing, update comment.
    if(is_numeric($comment_id)){
      $this->comments->updateComment($comment_id, $body);
    }
    // New, save comment.
    elseif(empty($comment_id)){
      $comment_id = $this->comments->saveComment($body, $entity_id, $reference_type);
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
  private function getCommentBody($id){
    $query = \Drupal::database()->select('ol_comment', 'mess');
    $query->addField('mess', 'body');
    $query->condition('mess.id', $id);
    $comment_data = $query->execute()->fetchObject();
    return $comment_data;
  }

}
