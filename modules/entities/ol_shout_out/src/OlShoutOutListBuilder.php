<?php

namespace Drupal\ol_shout_out;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OL Shout Out entities.
 *
 * @ingroup ol_shout_out
 */
class OlShoutOutListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OL Shout Out ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_shout_out\Entity\OlShoutOut $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_shout_out.edit_form',
      ['ol_shout_out' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
