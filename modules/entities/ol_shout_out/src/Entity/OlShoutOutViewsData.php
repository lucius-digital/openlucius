<?php

namespace Drupal\ol_shout_out\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Shout Out entities.
 */
class OlShoutOutViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
