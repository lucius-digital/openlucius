<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_text_docs\Services\OlCategories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class RemoveTextDocFromCategoryForm.
 */
class RemoveTextDocFromCategoryForm extends FormBase {


  /**
   * @var $categories
   */
  protected $categories;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_text_docs\Services\OlCategories $categories
   */
  public function __construct(OlCategories $categories) {
    $this->categories = $categories;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oltextdocs.categories')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_file_from_category';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-textdoc-id')),
    ];
    $form['submit'] = [
      '#prefix' => '<div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '30',
      '#attributes' => array('class' => array('btn btn-warning')),
      '#value' => $this->t('Yes, remove file from category'),
      '#suffix' => '</div>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $id_file = Html::escape($form_state->getValue('id'));
    $this->categories->removeTextDocFromCategory($id_file);
  }

}


