<?php

namespace Drupal\ol_social_question_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol social question settings entities.
 *
 * @ingroup ol_social_question_settings
 */
interface OlSocialQuestionSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol social question settings name.
   *
   * @return string
   *   Name of the Ol social question settings.
   */
  public function getName();

  /**
   * Sets the Ol social question settings name.
   *
   * @param string $name
   *   The Ol social question settings name.
   *
   * @return \Drupal\ol_social_question_settings\Entity\OlSocialQuestionSettingsInterface
   *   The called Ol social question settings entity.
   */
  public function setName($name);

  /**
   * Gets the Ol social question settings creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol social question settings.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol social question settings creation timestamp.
   *
   * @param int $timestamp
   *   The Ol social question settings creation timestamp.
   *
   * @return \Drupal\ol_social_question_settings\Entity\OlSocialQuestionSettingsInterface
   *   The called Ol social question settings entity.
   */
  public function setCreatedTime($timestamp);

}
