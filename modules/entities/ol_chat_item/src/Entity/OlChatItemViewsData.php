<?php

namespace Drupal\ol_chat_item\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for OL Chat Item entities.
 */
class OlChatItemViewsData extends EntityViewsData {

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
