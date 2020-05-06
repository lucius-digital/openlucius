<?php

namespace Drupal\ol_stream_item\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Stream Item entities.
 */
class OlStreamItemViewsData extends EntityViewsData {

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
