<?php

namespace Drupal\ol_post\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Post entities.
 */
class OlPostViewsData extends EntityViewsData {

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
