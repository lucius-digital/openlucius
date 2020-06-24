<?php

namespace Drupal\ol_culture_question_settings\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol culture question settings entities.
 */
class OlCultureQuestionSettingsViewsData extends EntityViewsData {

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
