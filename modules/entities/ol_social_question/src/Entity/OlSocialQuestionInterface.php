<?php

namespace Drupal\ol_social_question\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol social question entities.
 *
 * @ingroup ol_social_question
 */
interface OlSocialQuestionInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol social question name.
   *
   * @return string
   *   Name of the Ol social question.
   */
  public function getName();

  /**
   * Sets the Ol social question name.
   *
   * @param string $name
   *   The Ol social question name.
   *
   * @return \Drupal\ol_social_question\Entity\OlSocialQuestionInterface
   *   The called Ol social question entity.
   */
  public function setName($name);

  /**
   * Gets the Ol social question creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol social question.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol social question creation timestamp.
   *
   * @param int $timestamp
   *   The Ol social question creation timestamp.
   *
   * @return \Drupal\ol_social_question\Entity\OlSocialQuestionInterface
   *   The called Ol social question entity.
   */
  public function setCreatedTime($timestamp);

}
