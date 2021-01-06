<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_text_docs\Services\OlTextdocs;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class DeleteTextdocForm.
 */
class DeleteTextdocForm extends FormBase {

  /**
   * @var $textdocs
   */
  protected $textdocs;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_text_docs\Services\OlTextdocs $textdocs
   */
  public function __construct(OlTextdocs $textdocs) {
    $this->textdocs = $textdocs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oltextdocs.textdocs')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ol_delete_textdoc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => array('id' => array('remove-textdoc-id')),
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<button type="button" class="btn btn-default" data-dismiss="modal">' .t('Cancel').'</button>',
      '#allowed_tags' => ['button'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => array('class' => array('btn btn-danger')),
      '#value' => t('Yes, Delete Permanently'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $id = $form_state->getValue('id');
    $this->textdocs->removeTextdoc($id, true);
  }

}
