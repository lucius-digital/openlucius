<?php

namespace Drupal\ol_social_question\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ol social question entities.
 */
class OlSocialQuestionViewsData extends EntityViewsData {

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
