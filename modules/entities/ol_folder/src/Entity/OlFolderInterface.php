<?php

namespace Drupal\ol_folder\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OL Folder entities.
 *
 * @ingroup ol_folder
 */
interface OlFolderInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the OL Folder name.
   *
   * @return string
   *   Name of the OL Folder.
   */
  public function getName();

  /**
   * Sets the OL Folder name.
   *
   * @param string $name
   *   The OL Folder name.
   *
   * @return \Drupal\ol_folder\Entity\OlFolderInterface
   *   The called OL Folder entity.
   */
  public function setName($name);

  /**
   * Gets the OL Folder creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OL Folder.
   */
  public function getCreatedTime();

  /**
   * Sets the OL Folder creation timestamp.
   *
   * @param int $timestamp
   *   The OL Folder creation timestamp.
   *
   * @return \Drupal\ol_folder\Entity\OlFolderInterface
   *   The called OL Folder entity.
   */
  public function setCreatedTime($timestamp);

}
