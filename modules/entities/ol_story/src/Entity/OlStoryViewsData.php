<?php

namespace Drupal\ol_story\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Story entities.
 */
class OlStoryViewsData extends EntityViewsData {

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
