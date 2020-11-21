<?php

namespace Drupal\ol_icebreaker_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol icebreaker settings entities.
 *
 * @ingroup ol_icebreaker_settings
 */
class OlIcebreakerSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol icebreaker settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_icebreaker_settings.edit_form',
      ['ol_icebreaker_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
