<?php

namespace Drupal\ol_main\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountInterface;

class OLMainSubscriber implements EventSubscriberInterface {


  /**
   * Declare the property here to fix the error.
   */
  protected AccountInterface $account;

  /**
   * OLMainSubscriber constructor.
   */
  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  /**
   * Makes sure no anonymous users can not enter.
   * @param RequestEvent $event
   */
  public function checkAnonymous(RequestEvent $event) {

    // Get current request.
    $request = $event->getRequest();
    // Get current path.
    $current_path = $request->getPathInfo();
    // Check if this is /user/reset path.
    $user_reset = str_contains($current_path, '/user/reset/');
    // Check if this is a JS file in sites/default/files/js.
    $js_file = str_starts_with($current_path, '/sites/default/files/js/');
    if ($this->account->isAnonymous()
      && $current_path != '/user/login'
      && $current_path != '/user/password'
      && $current_path != '/register'
      && $user_reset != true
      && $js_file != true
    ){
      $event->setResponse(new RedirectResponse('/user/login', 301));
    }
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   */
  public function updateGroupVisitedTimestamp(RequestEvent $event) {

    // Get current request.
    $request = $event->getRequest();
    // Get current path.
    $current_path = $request->getPathInfo();
    // Check if we are on a group page.
    $is_group_page = str_starts_with($current_path, '/group/');

    if($is_group_page) {
      // Get current user id.
      $current_uid =$this->account->id();
      // Get group id.
      $path_args = explode('/', $current_path);
      $gid = $path_args[2];
      // Update timestamp, we use the 'changed' field.
      if(is_numeric($gid)){
        \Drupal::database()->update('ol_group_user')
          ->fields([
            'changed' => time(),
          ])
          ->condition('group_id', $gid)
          ->condition('member_uid', $current_uid)
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAnonymous', 100];
    $events[KernelEvents::REQUEST][] = ['updateGroupVisitedTimestamp'];
    return $events;
  }

}
