<?php

namespace Drupal\ol_culture_question_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol culture question settings entities.
 *
 * @ingroup ol_culture_question_settings
 */
interface OlCultureQuestionSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol culture question settings name.
   *
   * @return string
   *   Name of the Ol culture question settings.
   */
  public function getName();

  /**
   * Sets the Ol culture question settings name.
   *
   * @param string $name
   *   The Ol culture question settings name.
   *
   * @return \Drupal\ol_culture_question_settings\Entity\OlCultureQuestionSettingsInterface
   *   The called Ol culture question settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol culture question settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol culture question settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol culture question settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol culture question settings creation timestamp.
   *
   * @return \Drupal\ol_culture_question_settings\Entity\OlCultureQuestionSettingsInterface
   *   The called Ol culture question settings entity.
   */
  public function setCreatedTime($timestamp);

}
