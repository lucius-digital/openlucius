<?php

namespace Drupal\ol_culture_question\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol culture question entities.
 *
 * @ingroup ol_culture_question
 */
interface OlCultureQuestionInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol culture question name.
   *
   * @return string
   *   Name of the Ol culture question.
   */
  public function getName();

  /**
   * Sets the Ol culture question name.
   *
   * @param string $name
   *   The Ol culture question name.
   *
   * @return \Drupal\ol_culture_question\Entity\OlCultureQuestionInterface
   *   The called Ol culture question entity.
   */
  public function setName($name);

  /**
   * Gets the Ol culture question creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol culture question.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol culture question creation timestamp.
   *
   * @param int $timestamp
   *   The Ol culture question creation timestamp.
   *
   * @return \Drupal\ol_culture_question\Entity\OlCultureQuestionInterface
   *   The called Ol culture question entity.
   */
  public function setCreatedTime($timestamp);

}
