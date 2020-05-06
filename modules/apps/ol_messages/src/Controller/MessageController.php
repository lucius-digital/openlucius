<?php

namespace Drupal\ol_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_messages\Services\OlMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class messageController.
 */
class MessageController extends ControllerBase {

  /**
   * @var $comments
   */
  protected $messages;

  /**
   * @var $pager
   */
  protected $pager;

  /**
   * @var $pager_params
   */
  protected $pager_params;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $members
   */
  protected $members;

  /**
   * Constructor
   */
  public function __construct(OlMessages $messages, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members) {
    $this->messages = $messages;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
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
      $container->get('olmembers.members')

    );
  }

  /**
   * @param $gid
   *
   * @return array
   * @throws \Exception
   */
  public function getMessageList($gid){

    // Pager initialize, also needed for getMessagesList query.
    //$pager_parameters = \Drupal::service('pager.parameters');
    $page = $this->pager_params->findPage();
    $num_per_page = 10;
    $offset = $num_per_page * $page;

    // Get our messages data.
    $message_form = \Drupal::formBuilder()->getForm(\Drupal\ol_messages\Form\MessageForm::class);
    $message_list_data = $this->messages->getMessagesList(null, $num_per_page, $offset, null);
    $messages = $this->messages->renderMessagesList($message_list_data, 'list');

    // Pager, now that we have the total number of results, .
    $total_result = $this->messages->getMessagesList(null, null, null, true);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Build theme vars.
    $theme_vars = [
      'message_form' => $message_form,
      'messages' => $messages,
    ];
    // Create a render array with the search results.
    $render = [];
    $render[] = [
      '#theme' => 'messages_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => [
          'ol_messages/ol_messages'
        ],
      ],
    ];
    // Finally, add the pager to the render array, and return.
    $render[] = ['#type' => 'pager'];
    return $render;

  }

  /**
   * @param $id
   *
   * @return array
   * @throws \Exception
   */
  public function getMessage($id){
    // Get data.
    $message_data = $this->messages->getMessagesList($id);
    $message_title = $this->messages->getMessageTitle($message_data);
    $message = $this->messages->renderMessagesList($message_data, 'page');
    $comment_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\CommentForm::class, null, null, 'message', $id);
    $comment_items = $this->comments->getComments($id, 'message', 'asc');
    $current_user_picture = $this->members->getUserPictureUrl(); // Should move to CommentForm

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
          'ol_main/ol_comments'
        ],
      ],
    ];
    return $build;
  }



}
