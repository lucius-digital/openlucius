<?php

namespace Drupal\ol_culture_question;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol culture question entities.
 *
 * @ingroup ol_culture_question
 */
class OlCultureQuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol culture question ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_culture_question\Entity\OlCultureQuestion $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_culture_question.edit_form',
      ['ol_culture_question' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
