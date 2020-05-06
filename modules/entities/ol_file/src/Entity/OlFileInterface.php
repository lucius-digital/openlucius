<?php

namespace Drupal\ol_file\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol file entities.
 *
 * @ingroup ol_file
 */
interface OlFileInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol file name.
   *
   * @return string
   *   Name of the Ol file.
   */
  public function getName();

  /**
   * Sets the Ol file name.
   *
   * @param string $name
   *   The Ol file name.
   *
   * @return \Drupal\ol_file\Entity\OlFileInterface
   *   The called Ol file entity.
   */
  public function setName($name);

  /**
   * Gets the Ol file creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol file.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol file creation timestamp.
   *
   * @param int $timestamp
   *   The Ol file creation timestamp.
   *
   * @return \Drupal\ol_file\Entity\OlFileInterface
   *   The called Ol file entity.
   */
  public function setCreatedTime($timestamp);

}
