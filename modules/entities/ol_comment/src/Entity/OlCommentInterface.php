<?php

namespace Drupal\ol_comment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Comment entities.
 *
 * @ingroup ol_comment
 */
interface OlCommentInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Comment name.
   *
   * @return string
   *   Name of the OL Comment.
   */
  public function getName();

  /**
   * Sets the OL Comment name.
   *
   * @param string $name
   *   The OL Comment name.
   *
   * @return \Drupal\ol_comment\Entity\OlCommentInterface
   *   The called OL Comment entity.
   */
  public function setName($name);

  /**
   * Gets the OL Comment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Comment.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Comment creation timestamp.
   *
   * @param int $timestamp
   *   The OL Comment creation timestamp.
   *
   * @return \Drupal\ol_comment\Entity\OlCommentInterface
   *   The called OL Comment entity.
   */
  public function setCreatedTime($timestamp);

}
