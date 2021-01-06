<?php

namespace Drupal\ol_text_docs\Services;

use Drupal\Core\Url;
use Drupal\ol_text_doc\Entity\OlTextDoc;

/**
 * Class OlTextdocs.
 */
class OlTextDocs{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * @var $mail
   */
  protected $mail;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @param $route
   * @param $members
   * @param $stream
   * @param $mail
   * @param $groups
   * @param $comments
   * @param $files
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
  }


  /**
   * Saves a new Notebook.
   *
   * @param $name
   * @param $body
   * @param bool $send_mail
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveTextDoc($name, $body, $send_mail = false){
    // Get group id.
    $gid = $this->route->getParameter('gid');
    // Save new TextDoc.
    $doc = OlTextDoc::create([
      'name' => $name,
      'body' =>  $body,
      'group_id' => $gid
    ]);
    $doc->save();
    $text_doc_id = $doc->id();

    // Add stream item.
    $stream_body = $name; // Create new stream item.
    $this->stream->addStreamItem($gid, 'text_doc_added', $stream_body, 'notebooks', $text_doc_id); // Create stream item.
    // Mail if true
    if($send_mail == true){
      // Generate url and send mails.
      $url = Url::fromRoute('ol_text_docs.textdocs', ['gid' => $gid])->toString();
      $this->mail->sendMail($name, $url);
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your Notebook was added successfully.'));
    // Return id
    return $text_doc_id;
  }
  /**
   * @param $id
   * @param $name
   * @param $body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateTextDoc($id, $name, $body, $send_mail = false){
    // Update message  with spoofing protection.
    if($this->isDocOwner($id)) {
      $entity = OlTextDoc::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();

      // Update name in ol_file table | Duplicate stuff, guilty, will do better in future :)
      \Drupal::database()->update('ol_file')
        ->fields(['name' => $name])
        ->condition('entity_id', $id)
        ->condition('entity_type', 'text_doc')
        ->execute();

      // Mail if checked by user.
      if($send_mail == true){
        // Generate url and send mails.
        $gid = $this->route->getParameter('gid');
        $url = Url::fromRoute('ol_text_docs.text_doc', ['gid' => $gid, 'id' => $id])->toString();
        $this->mail->sendMail($name, $url);
      }
      // Add message.
      \Drupal::messenger()->addStatus(t('Your notebook was updated successfully.'));
    }
  }


  /**
   * @param null $ol_file_id
   * @param null $show_in_stream
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeTextdoc($id = null, $show_in_stream = null){
    // Get parameters from url
    $gid = $this->route->getParameter('gid');
    // Delete if file owner is true.
    if($this->isDocOwner($id)) {
      $name = $this->getTextDocName($id);
      // Delete reference from dbase (and search index).
      $ol_text_doc = OlTextDoc::load($id);
      $ol_text_doc->delete();

      // Add stream item.
      if($show_in_stream) {
        // We can't have this as dependency, else install profile will bitch during install.
        // So for now, procedural use of this service.
        $stream = \Drupal::service('olstream.stream');
        $stream->addStreamItem($gid, 'text_doc_removed', $name, 'notebooks', $id);
      }
      // Set message.
      \Drupal::messenger()->addStatus( $name .t(' successfully deleted.'));
    }
  }

  /**
   * @param $num_per_page
   * @param $offset
   * @param bool $get_total
   * @param null $category_id
   *
   * @return mixed
   */
  public function getTextDocListPage($num_per_page, $offset, $get_total = false, $category_id = null){
    // Get data
    $group_id = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'id');
    $query->condition('olt.group_id', $group_id);
    $query->condition('olt.status', 1);
    if($category_id > 0){
      $query->condition('olt.category_id', $category_id);
    }
    $query->orderBy('olt.created', 'desc');
     if ($get_total == false) {
     $query->range($offset, $num_per_page);
    }
    // Data for list.
    if ($get_total == false) {
      $textdocs_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      $textdocs_data = $query->countQuery()->execute()->fetchField();
    }
    return $textdocs_data;
  }

  /**
   * @param $textdoc_list_data
   * @return string
   */
  function renderTextDocListPage($textdoc_list_data){
    // Get data.
    $textdocs_html = '';
    $owner_show_modals = false;
    // We can't have this as dependency, else install protextdoc will bitch during install.
    // So for now, procedural use of this service.
    $categories = \Drupal::service('oltextdocs.categories');
    $has_categories = !empty($categories->getCategories());
    // Needed to redirect to current category, after removing a textdoc from a category.
    //$textdoc_row_data['current_path'] = $path .'?category='.$id_category;
    // Needed to show/hide category options in drop down.
    // Loop through textdocs and build html.
    foreach ($textdoc_list_data as $textdoc_data) {
      $textdoc_row_data = $this->buildTextDocDetails($textdoc_data->id);
      // If current user is owner somewhere, flag this true, so modals will show.
      if ($textdoc_row_data['owner'] == 1){
        $owner_show_modals = true;
      }
      // Needed to show/hide 'put in cat' / 'remove from cat' drop downs.
      $textdoc_row_data['has_categories'] = $has_categories;
      // Render the html row.
      $render = ['#theme' => 'text_doc_item_list_page', '#vars' => $textdoc_row_data];
      $textdocs_html .= \Drupal::service('renderer')->render($render);
    }

    // Render modals, only if user is owner of one of the textdocs.
    $textdoc_remove_modal_html = null;
    $textdoc_in_category_html = null;
    $remove_category_html = null;

    // If current user is owner in 1 of the records: show modals.
    if ($owner_show_modals){
      // Remove textdoc modal
      $vars['remove_textdoc_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_text_docs\Form\DeleteTextdocForm::class);
      $modal_render = ['#theme' => 'text_doc_modal_remove','#vars' => $vars];
      $textdoc_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
      // Put textdoc in category modal.
      $vars['textdoc_in_category'] = \Drupal::formBuilder()->getForm(\Drupal\ol_text_docs\Form\PlaceTextDocInCategoryForm::class);
      $modal2_render = ['#theme' => 'text_doc_modal_put_in_category','#vars' => $vars];
      $textdoc_in_category_html = \Drupal::service('renderer')->render($modal2_render);
    }
    return $textdocs_html .$textdoc_remove_modal_html .$textdoc_in_category_html .$remove_category_html;
  }

  /**
   * @param $id
   * @return mixed
   */
  public function getTextDocData($id){
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
   * @param $text_doc
   * @return string
   */
  public function renderTextDoc($text_doc){
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
    $text_doc_row_data['like_button'] = \Drupal::formBuilder()->getForm(\Drupal\ol_like\Form\LikeForm::class, 'textdoc', $text_doc->id);
    $text_doc_row_data['link'] = '/group/'.$text_doc->group_id.'/files/text_doc/'.$text_doc->id;
    if($text_doc_row_data['owner'] == true) {
      $text_doc_row_data['message_edit_form'] =
        \Drupal::formBuilder()->getForm(\Drupal\ol_text_docs\Form\TextDocForm::class, 'edit', $text_doc->id);
    }
    $text_doc_row_data['comment_count'] = $this->comments->getCommentCount($text_doc->id, 'text_doc', $text_doc->group_id);
    $text_doc_row_data['files'] = $this->files->getAttachedFiles('text_doc_attachment', $text_doc->id);
    // Render the data to html.
    $render = ['#theme' => 'text_doc_card', '#vars' => $text_doc_row_data];
    return \Drupal::service('renderer')->render($render);
  }

  /**
   * @param $id
   * @return mixed
   */
  private function buildTextDocDetails($id){
    // Get textdoc data details.
    $textdoc = $this->getTextDocData($id);
    // Build row.
    $textdoc_row_data['id'] = $textdoc->id;
    $textdoc_row_data['group_id'] = $textdoc->group_id;
    $textdoc_row_data['textdocname'] = shortenString($textdoc->name, 45);
    $textdoc_row_data['body_intro'] = shortenString($textdoc->body, 145);
    $textdoc_row_data['created'] = $textdoc->created;
    $textdoc_row_data['user_name'] = $this->members->getUserName($textdoc->user_id);
    $textdoc_row_data['category_name'] = $this->getTextDocCategoryName($textdoc->category_id);
    $textdoc_row_data['owner'] = $textdoc->user_id == $this->members->getUserId();
    $textdoc_row_data['category_id'] = $textdoc->category_id;
    $textdoc_row_data['textdoc_type'] = 'text_doc';
    $textdoc_row_data['url'] = Url::fromRoute('ol_text_docs.text_doc',
                                                ['gid' => $textdoc->group_id,
                                                'id' => $textdoc->id],
                                                ['query' => ['category' => $textdoc->category_id]]
                                              )->toString();
    return $textdoc_row_data;
  }

  /**
   * @param $data
   * @return mixed
   */
  public function getTextDocTitle($data){
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $data->id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  private function getTextDocCategoryName($id){
    $query = \Drupal::database()->select('ol_category', 'olc');
    $query->addField('olc', 'name');
    $query->condition('olc.id', $id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  private function getTextDocName($id){
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $id);
    return $query->execute()->fetchField();
  }


  /**
   * @param $id
   *
   * @return bool
   */
  private function isDocOwner($id){
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'user_id');
    $query->condition('olt.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }


}
