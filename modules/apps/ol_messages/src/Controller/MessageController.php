<?php

namespace Drupal\ol_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_messages\Services\OlMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling messages page requests.
 */
class MessageController extends ControllerBase {

  /**
   * Messages service.
   *
   * @var \Drupal\ol_messages\Services\OlMessages
   */
  protected $messages;

  /**
   * Pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  protected $pager;

  /**
   * Pager parameters service.
   *
   * @var \Drupal\Core\Pager\PagerParameters
   */
  protected $pager_params;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Sections service.
   *
   * @var \Drupal\ol_main\Services\OlSections
   */
  protected $sections;

  /**
   * MessageController constructor.
   *
   * @param \Drupal\ol_messages\Services\OlMessages $messages
   *   Messages service.
   * @param \Drupal\Core\Pager\PagerManager $pager
   *   Pager manager service.
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   *   Pager parameters service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_main\Services\OlSections $sections
   *   Sections service.
   */
  public function __construct(OlMessages $messages, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections) {
    $this->messages = $messages;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->sections = $sections;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmessages.messages'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members'),
      $container->get('olmain.sections')
    );
  }

  /**
   * Render the messages list page.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return array
   *   A render array for the messages list page.
   *
   * @throws \Exception
   */
  public function getMessageList($gid) {

    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 10;
    $offset = $num_per_page * $page;

    // Get messages data.
    $message_form = \Drupal::formBuilder()->getForm(
      '\Drupal\ol_messages\Form\MessageForm'
    );
    $message_list_data = $this->messages->getMessagesList(NULL, $num_per_page, $offset, NULL);
    $messages = $this->messages->renderMessagesList($message_list_data, 'list');
    $page_title = $this->sections->getSectionOverrideTitle('messages', 'Messages');

    // Pager, now that we have the total number of results.
    $total_result = $this->messages->getMessagesList(NULL, NULL, NULL, TRUE);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Build theme vars.
    $theme_vars = [
      'message_form' => $message_form,
      'messages' => $messages,
      'page_title' => $page_title,
    ];
    // Build render array.
    $render = [];
    $render[] = [
      '#theme' => 'messages_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => [
          'ol_messages/ol_messages',
        ],
      ],
    ];
    // Add pager and return.
    $render[] = ['#type' => 'pager'];
    return $render;
  }

  /**
   * Render a single message page with comments.
   *
   * @param int $id
   *   The message ID.
   *
   * @return array
   *   A render array for the message page.
   *
   * @throws \Exception
   */
  public function getMessage($id) {
    // Get data.
    $message_data = $this->messages->getMessagesList($id);
    $message_title = $this->messages->getMessageTitle($message_data);
    $message = $this->messages->renderMessagesList($message_data, 'page');
    // Comment form for the message.
    $comment_form = \Drupal::formBuilder()->getForm(
      '\Drupal\ol_main\Form\CommentForm', NULL, NULL, 'message', $id
    );
    $comment_items = $this->comments->getComments($id, 'message', 'asc');
    $current_user_picture = $this->members->getUserPictureUrl();

    // Build it.
    $theme_vars = [
      'message' => $message,
      'title' => $message_title,
      'comment_form' => $comment_form,
      'comment_items' => $comment_items,
      'current_user_picture' => $current_user_picture,
    ];
    $build = [
      '#theme' => 'message_page',
      '#vars' => $theme_vars,
      '#attached' => [
        'library' => [
          'ol_messages/ol_messages',
          'ol_main/ol_comments',
        ],
      ],
    ];
    return $build;
  }

}
