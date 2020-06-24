<?php

namespace Drupal\ol_like\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ol like entities.
 *
 * @ingroup ol_like
 */
interface OlLikeInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ol like name.
   *
   * @return string
   *   Name of the Ol like.
   */
  public function getName();

  /**
   * Sets the Ol like name.
   *
   * @param string $name
   *   The Ol like name.
   *
   * @return \Drupal\ol_like\Entity\OllikeInterface
   *   The called Ol like entity.
   */
  public function setName($name);

  /**
   * Gets the Ol like creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ol like.
   */
  public function getCreatedTime();

  /**
   * Sets the Ol like creation timestamp.
   *
   * @param int $timestamp
   *   The Ol like creation timestamp.
   *
   * @return \Drupal\ol_like\Entity\OllikeInterface
   *   The called Ol like entity.
   */
  public function setCreatedTime($timestamp);

}
