<?php

namespace Drupal\ol_culture_question\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol culture question entities.
 */
class OlCultureQuestionViewsData extends EntityViewsData {

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
