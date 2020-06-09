<?php

namespace Drupal\ol_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ol_main\Services\OlGroups;

/**
 * Class StreamController.
 */
class StreamController extends ControllerBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $form_builder, OlGroups $groups, OlStream $stream) {
    $this->members = $members;
    $this->form_builder = $form_builder;
    $this->groups = $groups;
    $this->stream = $stream;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('form_builder'),
      $container->get('olmain.groups'),
      $container->get('olstream.stream')
    );
  }

  public function getStream($gid){
    // Group uuid is used in Javascript, for socket.io room id and ajax calls.
    // This is for security hardening.
    $group_uuid = $this->groups->getGroupUuidById($gid);
    $username = $this->members->getUserName();
    $user_picture = $this->members->getUserPictureUrl();
    $node_server = \Drupal::config('ol_main.admin_settings')->get('nodejs_server_url');
    // Get forms.
    $stream_form = $this->form_builder->getForm(\Drupal\ol_stream\Form\StreamItemForm::class, $node_server);
    $load_more = $this->form_builder->getForm(\Drupal\ol_stream\Form\LoadPreviousStreamItemsForm::class);
    // This is needed to determine refreshing (via javascript).
    $last_message_timestamp = $this->stream->getLastMessageTimestamp($group_uuid);
    // Build it.
    $theme_vars = [
      'stream_form' => $stream_form,
      'load_more' => $load_more,
      'last_message_timestamp' => $last_message_timestamp,
      'node_server' => $node_server,
    ];
    return [
      '#theme' => 'stream_wrapper',
      '#attached' => [
        'library' => [
          'ol_stream/node_server', // Build dynamically in ol_stream_library_info_build().
          'ol_stream/stream',
        ],
        'drupalSettings' => [
          'group_uuid' => $group_uuid,
          'node_server' => $node_server,
          'username' => $username,
          'user_picture' => $user_picture
        ],
      ],
        '#vars' => $theme_vars,
    ];
  }
}
