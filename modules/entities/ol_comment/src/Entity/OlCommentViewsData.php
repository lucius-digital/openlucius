<?php

namespace Drupal\ol_comment\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Comment entities.
 */
class OlCommentViewsData extends EntityViewsData {

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
