<?php

namespace Drupal\ol_stream\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class LoadPreviousStreamItemsForm.
 */
class LoadPreviousStreamItemsForm extends FormBase {

  /**
   * @var $language_manager
   */
  protected $groups;

  /**
   * @var $language_manager
   */
  protected $stream;

  /**
   * Class constructor.
   * @param AccountInterface $account
   */
  public function __construct(OlGroups $groups, OlStream $stream) {
    $this->groups = $groups;
    $this->stream = $stream;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.groups'),
      $container->get('olstream.stream')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'load_previous_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['offset'] = [
      '#type' => 'textfield',
      '#default_value' => 0,
      '#attributes' => array('autocomplete' => 'off'),
    ];
    $form['actions'] = [
      '#type' => 'button',
      '#value' => t('Load 10 previous items'),
      '#attributes' => array('class' => array('btn btn-light btn-sm')),
      '#ajax' => [
        'callback' => '::submitStreamAjax',
        'event' => 'click',
        'disable-refocus' => true,
        'progress' => [
          'type' => 'bar',
          'message' => $this->t('Getting messages...'),
          ]
      ],
    ];
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
  public function submitStreamAjax(array &$form, FormStateInterface $form_state) {

    // Initiate response.
    $response = new AjaxResponse();
    // Get data.
    $group_id = $this->groups->getCurrentGroupId();
    $group_uuid = $this->groups->getGroupUuidById($group_id);
    $offset = Xss::filter($form_state->getValue('offset'));

    // Handle offset and length.
    // !) This need to be the same as OlStream.php ~L45, the default argument value.
    $new_items = 15;
    $offset_new = (int)$offset + $new_items;
    // Get html via stream service.
    $stream_data = $this->stream->getStreamList($group_uuid, $offset_new, $new_items);
    $stream_html = $this->stream->renderStreamList($stream_data, $offset_new, false);

    // Provide updated offset in hidden form texfield.
    $response->addCommand(new InvokeCommand('#edit-offset', 'val', [$offset_new]));
    // Provide html, append to span with id=append_here.
    $response->addCommand(new AfterCommand('#append_here', $stream_html));
    // Wipe all messages, so on page refresh nothing comes up.
    \Drupal::messenger()->deleteAll();
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
