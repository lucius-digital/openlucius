<?php

namespace Drupal\ol_weblinks\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\ol_board\Services\OlTasks;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_weblink\Entity\OlWeblink;
use Drupal\ol_weblinks\Services\OlWeblinks;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddWeblinkForm.
 */
class AddWeblinkForm extends FormBase {

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $tasks
   */
  protected $tasks;

  /**
   * @var $weblinks
   */
  protected $weblinks;

  /**
   * @var $route
   */
  protected $route;

  /**
   * Class constructor.
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlFiles $files, OlTasks $tasks, OlWeblinks $weblinks, CurrentRouteMatch $route) {
    $this->files = $files;
    $this->tasks = $tasks;
    $this->weblinks = $weblinks;
    $this->route = $route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.files'),
      $container->get('olboard.tasks'),
      $container->get('olweblinks.weblinks'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_weblink_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = null, $entity_id = null) {
    // Build form.
    $form['markup_form_row_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="row pt-2 pb-2 small text-muted">',
      '#allowed_tags' => ['div'],
    ];

    $form['link_title'] = [
      '#prefix' => '<div class="col-4 pl-2">',
      '#type' => 'textfield',
      '#required' => true,
      '#attributes' => [
        'placeholder' => t('Link title...'),
        'maxlength' => '150',
        'class' => [
          'form-control'
        ],
      ],
      '#suffix' => '</div>'
    ];
    $form['weblink'] = [
      '#prefix' => '<div class="col-4 pl-0">',
      '#type' => 'textfield',
      '#required' => true,
      '#attributes' => [
        'placeholder' => t('Link..'),
        'maxlength' => '150',
        'class' => [
          'form-control'
        ],
      ],
      '#suffix' => '</div>'
    ];
    $form['entity_id_weblink'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id,
      '#attributes' => [
        'id' => 'entity_id_weblink',
      ],
    ];
    $form['entity_type_weblink'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_type,
      '#attributes' => [
        'id' => 'entity_type_weblink',
      ],
    ];
    $form['submit'] = [
      '#prefix' => '<div class="col-2 pl-0">',
      '#type' => 'button',
      '#value' => t('Add'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-success btn-block'),
        'onsubmit' => 'return false',
      ],
      '#ajax' => [
        'callback' => '::callbackSaveWeblink',
        'event' => 'click',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Please wait...'),
        ],
      ],
      '#suffix' => '</div>'
    ];
    $form['hide-weblink-form'] = [
      '#prefix' => '<div class="col-2 pl-0">',
      '#type' => 'button',
      '#value' => 'X',
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-light text-muted'),
        'onsubmit' => 'return false',
      ],
      '#suffix' => '</div>'
    ];
    // End class="form-row"
    $form['markup_form_row_end'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
      '#allowed_tags' => ['div'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Interface requirement, dummy method.
  }
  /**
   * {@inheritdoc}
   */
  public function callbackSaveWeblink(array &$form, FormStateInterface $form_state) {
    // Init response.
    $response = new AjaxResponse();
    // Get form data and save files.

/*  {# internal link: 1 veld: alleen link #}
    {# external link: 2 velden #}
    {# dynamisch maken in link form #}

    name = 'internal' voor interne links.
    interne links automatisch juiste title geven realtime on-render.

*/

    $params['weblink'] = strip_tags($form_state->getValue('weblink'));
    $params['link_title'] = strip_tags($form_state->getValue('link_title'));
    $params['entity_id'] = strip_tags($form_state->getValue('entity_id_weblink'));
    $params['entity_type'] = strip_tags($form_state->getValue('entity_type_weblink'));
    $params['gid'] = $this->route->getParameter('gid');

    $this->weblinks->saveWeblink($params);

    // Query all weblinks for this task.
    $weblinks_items = $this->weblinks->getWeblinks($params);
    $weblinks_html = $this->weblinks->renderWeblinks($weblinks_items);
    // Update files in UI.
    // Remove all current files.
    $response->addCommand(new RemoveCommand('#linked-items-wrapper ul'));
    // Place all files (including new ones).
    $response->addCommand(new AppendCommand('#linked-items-wrapper', $weblinks_html));
    // Message.
    $message = t('Link saved successfully');
    $response->addCommand(new InvokeCommand('#linked-items-message', 'text', [$message]));
    $response->addCommand(new InvokeCommand('#linked-items-message', 'addClass', ['text-success']));
    // Empty and hide weblink form.
    $response->addCommand(new InvokeCommand('#weblink-form-wrapper', 'hide', ['fade']));
    $response->addCommand(new InvokeCommand('#add-weblink-form #edit-link-title', 'val', ['']));
    $response->addCommand(new InvokeCommand('#add-weblink-form #edit-weblink', 'val', ['']));


    // Clear messages and return response.
    \Drupal::messenger()->deleteAll();
    return $response;
  }

}


