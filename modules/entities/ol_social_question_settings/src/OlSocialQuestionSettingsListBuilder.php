<?php

namespace Drupal\ol_social_question_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol social question settings entities.
 *
 * @ingroup ol_social_question_settings
 */
class OlSocialQuestionSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol social question settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_social_question_settings\Entity\OlSocialQuestionSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_social_question_settings.edit_form',
      ['ol_social_question_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
