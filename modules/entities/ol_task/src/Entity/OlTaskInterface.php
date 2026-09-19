<?php

namespace Drupal\ol_task\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol task entities.
 *
 * @ingroup ol_task
 */
interface OlTaskInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol task name.
   *
   * @return string
   *   Name of the Ol task.
   */
  public function getName();

  /**
   * Sets the Ol task name.
   *
   * @param string $name
   *   The Ol task name.
   *
   * @return \Drupal\ol_task\Entity\OlTaskInterface
   *   The called Ol task entity.
   */
  public function setName($name);

  /**
   * Gets the Ol task creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol task.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol task creation timestamp.
   *
   * @param int $timestamp
   *   The Ol task creation timestamp.
   *
   * @return \Drupal\ol_task\Entity\OlTaskInterface
   *   The called Ol task entity.
   */
  public function setCreatedTime($timestamp);

}
