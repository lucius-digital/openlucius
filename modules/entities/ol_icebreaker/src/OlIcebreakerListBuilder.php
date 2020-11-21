<?php

namespace Drupal\ol_icebreaker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OL Icebreaker entities.
 *
 * @ingroup ol_icebreaker
 */
class OlIcebreakerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OL Icebreaker ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_icebreaker\Entity\OlIcebreaker $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_icebreaker.edit_form',
      ['ol_icebreaker' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
