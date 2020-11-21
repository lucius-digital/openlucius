<?php

namespace Drupal\ol_shout_out_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ol shout out settings entities.
 *
 * @ingroup ol_shout_out_settings
 */
class OlShoutOutSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ol shout out settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ol_shout_out_settings\Entity\OlShoutOutSettings $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ol_shout_out_settings.edit_form',
      ['ol_shout_out_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
