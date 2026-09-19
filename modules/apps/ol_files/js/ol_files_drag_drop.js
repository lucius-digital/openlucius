(function ($, Drupal, once, drupalSettings) {

  'use strict';

  /**
   * File drag and drop behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.fileDragDrop = {
    attach(context) {
      let draggedFile = null;

      /**
       * Make files draggable.
       */
      $(once('file-drag-item', '.media', context)).each(function () {
        const $file = $(this);
        const $fileData = $file.find('[data-file_id]').first();

        if (!$fileData.length) {
          return;
        }

        $file
          .addClass('file-drag-item')
          .attr('draggable', 'true');

        const fileId = $fileData.data('file_id');
        const fileName = $fileData.data('name');
        const currentFolderId = $fileData.data('folder_id');

        // console.log('[File Drag Drop] File found:', {
        //           fileId,
        //           fileName,
        //           currentFolderId,
        //         });

        /**
         * Handle drag start.
         */
        $file.on('dragstart.fileDragDrop', function (event) {
          draggedFile = this;

          $file.addClass('dragging');
          $('body').addClass('file-dragging');

          const dataTransfer = event.originalEvent.dataTransfer;
          const currentFileId = $fileData.data('file_id');
          const currentFileName = $fileData.data('name');
          const fileFolderId = $fileData.data('folder_id');

          dataTransfer.setData(
            'text/plain',
            String(currentFileId),
          );

          dataTransfer.effectAllowed = 'move';

          // console.log('[File Drag Drop] Drag started:', {
          //             fileId: currentFileId,
          //             fileName: currentFileName,
          //             currentFolderId: fileFolderId,
          //           });
        });

        /**
         * Handle drag end.
         */
        $file.on('dragend.fileDragDrop', function () {
          // console.log('[File Drag Drop] Drag ended.');

          $file.removeClass('dragging');
          $('body').removeClass('file-dragging');

          $('.folder-link')
            .closest('li')
            .removeClass('drag-over');

          draggedFile = null;
        });
      });

      /**
       * Make folder rows drop targets.
       */
      $(once('file-drop-folder', '.folder-link', context)).each(function () {
        const $folderLink = $(this);
        const $folderRow = $folderLink.closest('li');

        /*
         * Get the folder ID from the URL.
         *
         * Example:
         *
         * /group/2730/files?folder=160
         */
        const folderId = getFolderIdFromLink($folderLink);

        if (folderId === null) {
          // console.warn(
          //             '[File Drag Drop] Could not determine folder ID:',
          //             $folderLink.attr('href'),
          //           );

          return;
        }

        const folderName = $.trim(
          $folderLink.find('.ml-2').text(),
        );

        // console.log('[File Drag Drop] Folder found:', {
        //           folderId,
        //           folderName,
        //           href: $folderLink.attr('href'),
        //         });

        /**
         * Handle dragging over the entire folder row.
         */
        $folderRow.on('dragover.fileDragDrop', function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (
            event.originalEvent.dataTransfer
          ) {
            event.originalEvent.dataTransfer.dropEffect = 'move';
          }

          /*
           * Highlight this folder row.
           */
          $('.folder-link')
            .closest('li')
            .removeClass('drag-over');

          $folderRow.addClass('drag-over');

          // console.log(
          //             '[File Drag Drop] Dragging over folder:',
          //             folderId,
          //           );
        });

        /**
         * Handle dragging out of the folder row.
         */
        $folderRow.on('dragleave.fileDragDrop', function (event) {
          const relatedTarget =
            event.originalEvent.relatedTarget;

          /*
           * Only remove the highlight when the pointer has
           * actually left the entire folder row.
           */
          if (
            !relatedTarget ||
            !$folderRow[0].contains(relatedTarget)
          ) {
            $folderRow.removeClass('drag-over');
          }
        });

        /**
         * Handle dropping on the entire folder row.
         */
        $folderRow.on('drop.fileDragDrop', function (event) {
          event.preventDefault();
          event.stopPropagation();

          $folderRow.removeClass('drag-over');

          // console.log(
          //             '[File Drag Drop] Drop event received on folder row:',
          //             folderId,
          //           );

          if (!draggedFile) {
            // console.warn(
            //               '[File Drag Drop] No dragged file found.',
            //             );

            return;
          }

          const $file = $(draggedFile);
          const $fileData = $file.find('[data-file_id]').first();

          if (!$fileData.length) {
            // console.error(
            //               '[File Drag Drop] Could not find file data.',
            //             );

            return;
          }

          const fileId = $fileData.data('file_id');
          const fileName = $fileData.data('name');

          /*
           * The file's current folder.
           *
           * For an unfiled file this should normally be 0.
           */
          const currentFolderId =
            $fileData.data('folder_id');

          /*
           * Destination folder.
           */
          const destinationFolderId = folderId;

          // console.log(
          //             '[File Drag Drop] DROP DETECTED:',
          //             {
          //               fileId,
          //               fileName,
          //               currentFolderId,
          //               destinationFolderId,
          //               destinationFolderName: folderName,
          //             },
          //           );

          /*
           * Explicit debugging.
           */
          // console.log(
          //             '[File Drag Drop] File ID:',
          //             fileId,
          //           );

          // console.log(
          //             '[File Drag Drop] Current Folder ID:',
          //             currentFolderId,
          //           );

          // console.log(
          //             '[File Drag Drop] Destination Folder ID:',
          //             destinationFolderId,
          //           );

          /*
           * --------------------------------------------------------
           * SAME FOLDER
           * --------------------------------------------------------
           */
          if (
            String(currentFolderId) ===
            String(destinationFolderId)
          ) {
            // console.log(
            //               '[File Drag Drop] Same folder - no AJAX request.',
            //             );

            return;
          }

          /*
           * Find the current folder row.
           */
          const $currentFolder =
            findFolder(currentFolderId);

          if (!$currentFolder.length) {
            // console.warn(
            //               '[File Drag Drop] Current folder row not found:',
            //               currentFolderId,
            //             );
          }

          /*
           * Move the file.
           */
          moveFileToFolder(
            fileId,
            destinationFolderId,
            $file,
            $currentFolder,
            $folderRow,
          );
        });
      });

      /**
       * Get folder ID from a folder link.
       *
       * Example:
       *
       * /group/2730/files?folder=160
       *
       * @param {jQuery} $folderLink
       *   The folder link.
       *
       * @return {string|null}
       *   The folder ID or NULL.
       */
      function getFolderIdFromLink($folderLink) {
        if (!$folderLink || !$folderLink.length) {
          return null;
        }

        const href = $folderLink.attr('href');

        if (!href) {
          return null;
        }

        try {
          const url = new URL(
            href,
            window.location.origin,
          );

          const folderId =
            url.searchParams.get('folder');

          if (folderId === null || folderId === '') {
            return null;
          }

          return folderId;
        }
        catch (error) {
          // console.error(
          //             '[File Drag Drop] Could not parse folder URL:',
          //             href,
          //             error,
          //           );

          return null;
        }
      }

      /**
       * Find a folder row by folder ID.
       *
       * @param {number|string} folderId
       *   The folder ID.
       *
       * @return {jQuery}
       *   The folder row.
       */
      function findFolder(folderId) {
        let $result = $();

        /*
         * There is no need to search if the file is unfiled.
         */
        if (
          folderId === undefined ||
          folderId === null ||
          String(folderId) === '0'
        ) {
          return $result;
        }

        $('.folder-link').each(function () {
          const $folderLink = $(this);

          const thisFolderId =
            getFolderIdFromLink($folderLink);

          if (
            thisFolderId !== null &&
            String(thisFolderId) === String(folderId)
          ) {
            $result = $folderLink.closest('li');

            return false;
          }
        });

        return $result;
      }

      /**
       * Move a file into a folder.
       *
       * @param {number} fileId
       *   The file ID.
       * @param {number|string} folderId
       *   The destination folder ID.
       * @param {jQuery} $file
       *   The file element.
       * @param {jQuery} $currentFolder
       *   The current folder row.
       * @param {jQuery} $destinationFolder
       *   The destination folder row.
       */
      function moveFileToFolder(
        fileId,
        folderId,
        $file,
        $currentFolder,
        $destinationFolder,
      ) {
        const groupId = drupalSettings.group_id;

        // console.log(
        //           '[File Drag Drop] Preparing AJAX request:',
        //           {
        //             groupId,
        //             fileId,
        //             folderId,
        //           },
        //         );

        /*
         * Make sure the group ID is available.
         */
        if (!groupId) {
          // console.error(
          //             '[File Drag Drop] drupalSettings.group_id is missing.',
          //           );

          alert(
            Drupal.t(
              'An error occurred, please contact administrator.',
            ),
          );

          return;
        }

        /*
         * Send the move request to Drupal.
         */
        $.ajax({
          data: {
            file_id: fileId,
            folder_id: folderId,
          },
          url: Drupal.url(
            'group/' + groupId + '/files/move-file',
          ),
          type: 'POST',
        }).done(function (response) {
          // console.log(
          //             '[File Drag Drop] Drupal response:',
          //             response,
          //           );

          /*
           * Drupal reported an error.
           */
          if (response.success === false) {
            // console.error(
            //               '[File Drag Drop] Drupal reported an error:',
            //               response,
            //             );

            return;
          }

          /*
           * Successful move.
           */
          if (response.success === true) {
            // console.log(
            //               '[File Drag Drop] Move successful:',
            //               {
            //                 groupId,
            //                 fileId,
            //                 folderId,
            //               },
            //             );

            /*
             * Remove the file from the current list.
             */
            $file.fadeOut(200, function () {
              $(this).remove();
            });

            /*
             * Decrease current folder count.
             */
            if ($currentFolder.length) {
              updateFolderCount(
                $currentFolder,
                -1,
              );
            }

            /*
             * Increase destination folder count.
             */
            updateFolderCount(
              $destinationFolder,
              1,
            );
          }
        }).fail(function (xhr) {
          // console.error(
          //             '[File Drag Drop] AJAX request failed:',
          //             xhr.status,
          //             xhr.responseText,
          //           );

          alert(
            Drupal.t(
              'An error occurred, please contact administrator.',
            ),
          );
        });
      }

      /**
       * Update a folder's file count.
       *
       * @param {jQuery} $folder
       *   The folder row.
       * @param {number} change
       *   The count change.
       */
      function updateFolderCount($folder, change) {
        if (!$folder || !$folder.length) {
          return;
        }

        /*
         * The badge is directly inside the folder <li>.
         */
        const $count = $folder
          .find('.badge.badge-light.badge-pill')
          .first();

        if (!$count.length) {
          // console.warn(
          //             '[File Drag Drop] Folder count badge not found.',
          //           );

          return;
        }

        const currentCount =
          parseInt(
            $.trim($count.text()),
            10,
          ) || 0;

        const newCount = Math.max(
          0,
          currentCount + change,
        );

        const folderId = getFolderIdFromLink(
          $folder.find('.folder-link'),
        );

        // console.log(
        //           '[File Drag Drop] Updating folder count:',
        //           {
        //             folderId,
        //             oldCount: currentCount,
        //             change,
        //             newCount,
        //           },
        //         );

        $count.text(newCount);
      }
    },
  };

})(jQuery, Drupal, once, drupalSettings);
