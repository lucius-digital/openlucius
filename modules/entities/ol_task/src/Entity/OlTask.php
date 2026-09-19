<?php

namespace Drupal\ol_task\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Ol task entity.
 *
 * @ingroup ol_task
 *
 * @ContentEntityType(
 *   id = "ol_task",
 *   label = @Translation("Ol task"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ol_task\OlTaskListBuilder",
 *     "views_data" = "Drupal\ol_task\Entity\OlTaskViewsData",
 *
 *     "access" = "Drupal\ol_task\OlTaskAccessControlHandler",
 *   },
 *   base_table = "ol_task",
 *   translatable = FALSE,
 *   admin_permission = "administer ol task entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 * )
 */
class OlTask extends ContentEntityBase implements OlTaskInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Ol task entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Ol task entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Ol task is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('description'))
      ->setDescription(t('description'))
      ->setRequired(TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t('The Group this file belongs to.'))
      ->setRequired(TRUE);

    $fields['list_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t('The Group this file belongs to.'))
      ->setRequired(TRUE);

    $fields['assignee_uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t('The Group this file belongs to.'))
      ->setRequired(TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Due date'))
      ->setSettings(['max_length' => 11])
      ->setDescription(t('due_date.'))
      ->setRequired(TRUE);

    $fields['task_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('Status.'))
      ->setRequired(TRUE);

    $fields['column'] = BaseFieldDefinition::create('string')
      ->setLabel(t('column'))
      ->setDescription(t('column.'))
      ->setRequired(TRUE);

    $fields['priority'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('priority'))
      ->setDescription(t('priority.'))
      ->setRequired(TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('weight'))
      ->setDescription(t('weight.'));

    $fields['done'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('done'))
      ->setDescription(t('done.'))
      ->setRequired(TRUE);

    $fields['opened'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Opened by who and when'))
      ->setDescription(t('Opened by who and when'))
      ->setStorageRequired(TRUE);

    return $fields;
  }

}
