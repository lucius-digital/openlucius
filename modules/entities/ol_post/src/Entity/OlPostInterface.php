<?php

namespace Drupal\ol_post\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Post entities.
 *
 * @ingroup ol_post
 */
interface OlPostInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Post name.
   *
   * @return string
   *   Name of the OL Post.
   */
  public function getName();

  /**
   * Sets the OL Post name.
   *
   * @param string $name
   *   The OL Post name.
   *
   * @return \Drupal\ol_post\Entity\OlPostInterface
   *   The called OL Post entity.
   */
  public function setName($name);

  /**
   * Gets the OL Post creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Post.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Post creation timestamp.
   *
   * @param int $timestamp
   *   The OL Post creation timestamp.
   *
   * @return \Drupal\ol_post\Entity\OlPostInterface
   *   The called OL Post entity.
   */
  public function setCreatedTime($timestamp);

}
