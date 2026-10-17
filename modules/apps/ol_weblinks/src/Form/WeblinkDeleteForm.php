<?php

namespace Drupal\ol_weblinks\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_weblinks\Services\OlWeblinks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting weblinks.
 */
class WeblinkDeleteForm extends FormBase {

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Weblinks service.
   *
   * @var \Drupal\ol_weblinks\Services\OlWeblinks
   */
  protected $weblinks;

  /**
   * WeblinkDeleteForm constructor.
   *
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   The comments service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The files service.
   * @param \Drupal\ol_weblinks\Services\OlWeblinks $weblinks
   *   The weblinks service.
   */
  public function __construct(OlComments $comments, OlFiles $files, OlWeblinks $weblinks) {
    $this->comments = $comments;
    $this->files = $files;
    $this->weblinks = $weblinks;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.comments'),
      $container->get('olmain.files'),
      $container->get('olweblinks.weblinks')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weblink_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build form.
    $form['ol_weblink_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => [
        'id' => 'ol-weblink-id',
      ],
    ];
    $form['delete_weblink_btn'] = [
      '#type' => 'button',
      '#value' => 'button',
      '#attributes' => [
        'class' => ['hidden'],
      ],
      '#ajax' => [
        'callback' => '::deleteWeblink',
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Delete a weblink via AJAX.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function deleteWeblink(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Get data.
    $ol_weblink_id = Html::escape($form_state->getValue('ol_weblink_id'));

    // Existing, update comment.
    if (is_numeric($ol_weblink_id)) {
      // Init delete weblink.
      $success = $this->weblinks->deleteWeblink($ol_weblink_id);
      // Remove weblink element if success.
      if ($success) {
        $response->addCommand(new InvokeCommand('#weblink-li-id_' . $ol_weblink_id, 'hide', ['fade']));
      }
      // Clear messages and return response.
      \Drupal::messenger()->deleteAll();
      return $response;
    }
  }

}
