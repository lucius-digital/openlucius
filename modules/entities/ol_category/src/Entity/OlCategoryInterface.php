<?php

namespace Drupal\ol_category\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Category entities.
 *
 * @ingroup ol_category
 */
interface OlCategoryInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Category name.
   *
   * @return string
   *   Name of the OL Category.
   */
  public function getName();

  /**
   * Sets the OL Category name.
   *
   * @param string $name
   *   The OL Category name.
   *
   * @return \Drupal\ol_category\Entity\OlCategoryInterface
   *   The called OL Category entity.
   */
  public function setName($name);

  /**
   * Gets the OL Category creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Category.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Category creation timestamp.
   *
   * @param int $timestamp
   *   The OL Category creation timestamp.
   *
   * @return \Drupal\ol_category\Entity\OlCategoryInterface
   *   The called OL Category entity.
   */
  public function setCreatedTime($timestamp);

}
