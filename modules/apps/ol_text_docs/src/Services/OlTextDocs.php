<?php

namespace Drupal\ol_text_docs\Services;

use Drupal\Core\Url;
use Drupal\ol_text_doc\Entity\OlTextDoc;

/**
 * Service for managing text documents.
 */
class OlTextDocs {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Stream service.
   *
   * @var \Drupal\ol_stream\Services\OlStream
   */
  protected $stream;

  /**
   * Mail service.
   *
   * @var \Drupal\ol_main\Services\OlMail
   */
  protected $mail;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Notifications service.
   *
   * @var \Drupal\ol_notifications\Services\OlNotifications
   */
  protected $notifications;

  /**
   * OlTextDocs constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   Current route match service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\ol_main\Services\OlMail $mail
   *   Mail service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   * @param \Drupal\ol_notifications\Services\OlNotifications $notifications
   *   Notifications service.
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files, $notifications) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
    $this->notifications = $notifications;
  }

  /**
   * Save a new text document.
   *
   * @param string $name
   *   The document name.
   * @param string $body
   *   The document body.
   * @param bool $sendMail
   *   Whether to send email notification.
   *
   * @return int|string|null
   *   The new text document ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveTextDoc($name, $body, $sendMail = FALSE) {
    // Get group id.
    $gid = $this->route->getParameter('gid');
    // Save new TextDoc.
    $doc = OlTextDoc::create([
      'name' => $name,
      'body' => $body,
      'group_id' => $gid,
    ]);
    $doc->save();
    $text_doc_id = $doc->id();
    // Generate url for mails.
    $url = Url::fromRoute('ol_text_docs.text_doc', ['gid' => $gid, 'id' => $text_doc_id])->toString();
    $cta_text = t('Find Out More');
    // Send @-mentions.
    $this->notifications->sendMentions($body, $url);
    // Add stream item.
    $this->stream->addStreamItem($gid, 'text_doc_added', $name, 'notebooks', $text_doc_id);
    // Mail if true.
    if ($sendMail == TRUE) {
      // Mail vars.
      $mail_body = t('A new notebook was added: @name:', ['@name' => $name]);
      $subject = t('New notebook: @name', ['@name' => $name]);
      // Send mails via service.
      $this->mail->sendMail($subject, $url, $mail_body, NULL, NULL, NULL, $cta_text, NULL, shortenString(strip_tags($body), 300));
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your Notebook was added successfully.'));
    // Return id.
    return $text_doc_id;
  }

  /**
   * Update an existing text document.
   *
   * @param int $id
   *   The text document ID.
   * @param string $name
   *   The new name.
   * @param string $body
   *   The new body.
   * @param bool $sendMail
   *   Whether to send email notification.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateTextDoc($id, $name, $body, $sendMail = FALSE) {
    // Update message with spoofing protection.
    if ($this->isDocOwner($id)) {
      $entity = OlTextDoc::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();
      // Update name in ol_file table.
      \Drupal::database()->update('ol_file')
        ->fields(['name' => $name])
        ->condition('entity_id', $id)
        ->condition('entity_type', 'text_doc')
        ->execute();
      // Generate url for emailing.
      $gid = $this->route->getParameter('gid');
      $url = Url::fromRoute('ol_text_docs.text_doc', ['gid' => $gid, 'id' => $id])->toString();
      // Send @-mentions.
      $this->notifications->sendMentions($body, $url);
      // Mail if checked by user.
      if ($sendMail == TRUE) {
        $this->mail->sendMail($name, $url);
      }
      // Add message.
      \Drupal::messenger()->addStatus(t('Your notebook was updated successfully.'));
    }
  }

  /**
   * Remove a text document.
   *
   * @param int|null $id
   *   The text document ID.
   * @param bool|null $showInStream
   *   Whether to show in stream.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeTextdoc($id = NULL, $showInStream = NULL) {
    // Get parameters from url.
    $gid = $this->route->getParameter('gid');
    // Delete if file owner is true.
    if ($this->isDocOwner($id)) {
      $name = $this->getTextDocName($id);
      // Delete reference from dbase (and search index).
      $ol_text_doc = OlTextDoc::load($id);
      $ol_text_doc->delete();

      // Add stream item.
      if ($showInStream) {
        // We can't have this as dependency, else install profile will bitch
        // during install. So for now, procedural use of this service.
        $stream = \Drupal::service('olstream.stream');
        $stream->addStreamItem($gid, 'text_doc_removed', $name, 'notebooks', $id);
      }
      // Set message.
      \Drupal::messenger()->addStatus(t('@name successfully deleted.', ['@name' => $name]));
    }
  }

  /**
   * Get text document list for pagination.
   *
   * @param int $numPerPage
   *   Number of items per page.
   * @param int $offset
   *   Offset for pagination.
   * @param bool $getTotal
   *   Whether to get total count.
   * @param int|null $categoryId
   *   Optional category ID filter.
   *
   * @return mixed
   *   Array of text documents or count.
   */
  public function getTextDocListPage($numPerPage, $offset, $getTotal = FALSE, $categoryId = NULL) {
    // Get data.
    $group_id = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'id');
    $query->condition('olt.group_id', $group_id);
    $query->condition('olt.status', 1);
    if ($categoryId > 0) {
      $query->condition('olt.category_id', $categoryId);
    }
    $query->orderBy('olt.changed', 'desc');
    if ($getTotal == FALSE) {
      $query->range($offset, $numPerPage);
    }
    // Data for list.
    if ($getTotal == FALSE) {
      $textdocs_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($getTotal == TRUE) {
      $textdocs_data = $query->countQuery()->execute()->fetchField();
    }
    return $textdocs_data;
  }

  /**
   * Render text document list page.
   *
   * @param array $textdoc_list_data
   *   Array of text document data.
   *
   * @return string
   *   Rendered HTML.
   */
  public function renderTextDocListPage($textdoc_list_data) {
    // Get data.
    $textdocs_html = '';
    $owner_show_modals = FALSE;
    // We can't have this as dependency, else install profile will bitch
    // during install. So for now, procedural use of this service.
    $categories = \Drupal::service('oltextdocs.categories');
    $has_categories = !empty($categories->getCategories());
    // Loop through textdocs and build html.
    foreach ($textdoc_list_data as $textdoc_data) {
      $textdoc_row_data = $this->buildTextDocDetails($textdoc_data->id);
      // If current user is owner somewhere, flag this true,
      // so modals will show.
      if ($textdoc_row_data['owner'] == 1) {
        $owner_show_modals = TRUE;
      }
      // Needed to show/hide 'put in cat' / 'remove from cat' drop downs.
      $textdoc_row_data['has_categories'] = $has_categories;
      // Render the html row.
      $render = ['#theme' => 'text_doc_item_list_page', '#vars' => $textdoc_row_data];
      $textdocs_html .= \Drupal::service('renderer')->render($render);
    }

    // Render modals, only if user is owner of one of the textdocs.
    $textdoc_remove_modal_html = NULL;
    $textdoc_in_category_html = NULL;
    $remove_category_html = NULL;

    // If current user is owner in 1 of the records: show modals.
    if ($owner_show_modals) {
      // Remove textdoc modal.
      $vars['remove_textdoc_modal'] = \Drupal::formBuilder()->getForm('Drupal\ol_text_docs\Form\DeleteTextdocForm');
      $modal_render = ['#theme' => 'text_doc_modal_remove', '#vars' => $vars];
      $textdoc_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
      // Put textdoc in category modal.
      $vars['textdoc_in_category'] = \Drupal::formBuilder()->getForm('Drupal\ol_text_docs\Form\PlaceTextDocInCategoryForm');
      $modal2_render = ['#theme' => 'text_doc_modal_put_in_category', '#vars' => $vars];
      $textdoc_in_category_html = \Drupal::service('renderer')->render($modal2_render);
    }
    return $textdocs_html . $textdoc_remove_modal_html . $textdoc_in_category_html . $remove_category_html;
  }

  /**
   * Get text document data by ID.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return object
   *   The text document data object.
   */
  public function getTextDocData($id) {
    // Get message detail data.
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'body');
    $query->addField('olt', 'group_id');
    $query->addField('olt', 'category_id');
    $query->addField('olt', 'id');
    $query->addField('olt', 'name');
    $query->addField('olt', 'created');
    $query->addField('olt', 'changed');
    $query->addField('olt', 'user_id');
    $query->addField('user', 'name', 'username');
    $query->condition('olt.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = olt.user_id');
    return $query->execute()->fetchObject();
  }

  /**
   * Render a single text document.
   *
   * @param object $text_doc
   *   The text document data object.
   *
   * @return string
   *   Rendered HTML.
   */
  public function renderTextDoc($text_doc) {
    // Build vars and render HTML rows via twig file.
    $text_doc_row_data['body'] = $text_doc->body;
    $text_doc_row_data['name'] = $text_doc->name;
    $text_doc_row_data['created'] = $text_doc->created;
    $text_doc_row_data['changed'] = $text_doc->changed;
    $text_doc_row_data['username'] = $text_doc->username;
    $text_doc_row_data['id'] = $text_doc->id;
    $text_doc_row_data['id_group'] = $text_doc->group_id;
    $text_doc_row_data['user_id'] = $text_doc->user_id;
    $text_doc_row_data['owner'] = $text_doc->user_id == $this->members->getUserId();
    $text_doc_row_data['user_picture'] = $this->members->getUserPictureUrl($text_doc->user_id);
    $text_doc_row_data['like_button'] = \Drupal::formBuilder()->getForm('Drupal\ol_like\Form\LikeForm', 'textdoc', $text_doc->id);
    $text_doc_row_data['link'] = '/group/' . $text_doc->group_id . '/files/text_doc/' . $text_doc->id;
    if ($text_doc_row_data['owner'] == TRUE) {
      $text_doc_row_data['message_edit_form'] =
        \Drupal::formBuilder()->getForm('Drupal\ol_text_docs\Form\TextDocForm', 'edit', $text_doc->id);
    }
    $text_doc_row_data['comment_count'] = $this->comments->getCommentCount($text_doc->id, 'text_doc', $text_doc->group_id);
    $text_doc_row_data['files'] = $this->files->getAttachedFiles('text_doc_attachment', $text_doc->id);
    // Weblinks.
    // Set params.
    $params['gid'] = $text_doc->group_id;
    $params['entity_id'] = $text_doc->id;
    $params['entity_type'] = 'text_doc';
    // Get weblinks forms.
    $weblinks = \Drupal::service('olweblinks.weblinks');
    $weblink_form = \Drupal::formBuilder()->getForm('Drupal\ol_weblinks\Form\AddWeblinkForm', 'text_doc', $text_doc->id);
    $weblink_delete_form = \Drupal::formBuilder()->getForm('Drupal\ol_weblinks\Form\WeblinkDeleteForm');
    // Get weblinks html.
    $weblinks_items = $weblinks->getWeblinks($params);
    $weblinks_html = $weblinks->renderWeblinks($weblinks_items);
    // Weblinks twig vars.
    $text_doc_row_data['weblink_form'] = $weblink_form;
    $text_doc_row_data['weblink_delete_form'] = $weblink_delete_form;
    $text_doc_row_data['weblinks_html'] = $weblinks_html;
    // Render the data to html.
    $render = ['#theme' => 'text_doc_card', '#vars' => $text_doc_row_data];
    return \Drupal::service('renderer')->render($render);
  }

  /**
   * Build text document details for list rendering.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return array
   *   Array of text document details.
   */
  private function buildTextDocDetails($id) {
    // Get textdoc data details.
    $textdoc = $this->getTextDocData($id);
    // Build row.
    $textdoc_row_data['id'] = $textdoc->id;
    $textdoc_row_data['group_id'] = $textdoc->group_id;
    $textdoc_row_data['textdocname'] = shortenString($textdoc->name, 45);
    $textdoc_row_data['body_intro'] = shortenString($textdoc->body, 350);
    $textdoc_row_data['created'] = $textdoc->created;
    $textdoc_row_data['user_name'] = $this->members->getUserName($textdoc->user_id);
    $textdoc_row_data['category_name'] = $this->getTextDocCategoryName($textdoc->category_id);
    $textdoc_row_data['owner'] = $textdoc->user_id == $this->members->getUserId();
    $textdoc_row_data['category_id'] = $textdoc->category_id;
    $textdoc_row_data['textdoc_type'] = 'text_doc';
    $textdoc_row_data['url'] = Url::fromRoute('ol_text_docs.text_doc',
      [
        'gid' => $textdoc->group_id,
        'id' => $textdoc->id,
      ],
      ['query' => ['category' => $textdoc->category_id]]
    )->toString();
    return $textdoc_row_data;
  }

  /**
   * Get all text documents data for a group.
   *
   * @return array
   *   Array of text document objects.
   */
  public function getTextDocsData() {
    // Get data.
    $group_id = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'id');
    $query->addField('olt', 'name');
    $query->addField('olt', 'group_id');
    $query->addField('olt', 'weight');
    $query->addField('olt', 'parent_id');
    $query->condition('olt.group_id', $group_id);
    $query->condition('olt.status', 1);
    $query->orderBy('olt.weight', 'asc');
    return $query->execute()->fetchAll();
  }

  /**
   * Update text document page positions.
   *
   * @param string $orderedItems
   *   JSON encoded ordered items.
   * @param string $uuid
   *   The group UUID.
   *
   * @return bool
   *   TRUE if positions were updated successfully.
   */
  public function updatePagePositions($orderedItems, $uuid) {
    // Decode.
    $orderedItems = json_decode($orderedItems);
    // Security hardening.
    $group_id = $this->groups->getGroupIdByUuid($uuid);
    $in_group = $this->members->checkUserInGroup($group_id);
    if ($in_group && $group_id) {
      // Loop through ordered items and save data.
      foreach ($orderedItems as $item) {
        \Drupal::database()->update('ol_text_doc')
          ->fields([
            'weight' => $item->weight,
            'parent_id' => $item->parent_id,
          ])
          ->condition('group_id', $group_id)
          ->condition('id', $item->id)
          ->execute();
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the text document title.
   *
   * @param object $data
   *   The text document data object.
   *
   * @return string
   *   The text document title.
   */
  public function getTextDocTitle($data) {
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $data->id);
    return $query->execute()->fetchField();
  }

  /**
   * Get the category name for a text document.
   *
   * @param int $id
   *   The category ID.
   *
   * @return string
   *   The category name.
   */
  private function getTextDocCategoryName($id) {
    $query = \Drupal::database()->select('ol_category', 'olc');
    $query->addField('olc', 'name');
    $query->condition('olc.id', $id);
    return $query->execute()->fetchField();
  }

  /**
   * Get the text document name.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return string
   *   The text document name.
   */
  private function getTextDocName($id) {
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $id);
    return $query->execute()->fetchField();
  }

  /**
   * Check if current user is the text document owner.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return bool
   *   TRUE if user is the owner.
   */
  private function isDocOwner($id) {
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'user_id');
    $query->condition('olt.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

}
