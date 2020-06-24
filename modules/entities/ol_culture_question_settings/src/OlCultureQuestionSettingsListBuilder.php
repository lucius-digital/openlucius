<?php

namespace Drupal\ol_culture_question_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol culture question settings entities.
 *
 * @ingroup ol_culture_question_settings
 */
class OlCultureQuestionSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol culture question settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_culture_question_settings\Entity\OlCultureQuestionSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_culture_question_settings.edit_form',
      ['ol_culture_question_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
