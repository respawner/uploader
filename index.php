<?php

/*
 * Uploader - Share files for a limited time
 * Copyright (C) 2014 Guillaume Mazoyer <gmazoyer@gravitons.in>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
 */

require_once 'includes/config.defaults.php';
require_once 'includes/database.php';
require_once 'includes/file.php';
require_once 'includes/upload.php';
require_once 'includes/utils.php';
require_once 'config.php';

final class Uploader {
  private $config;
  private $db;

  function __construct($config) {
    $this->config = $config;
    $this->db = new Database();

    if (!file_exists($this->config['uploads_directory'])) {
      mkdir($this->config['uploads_directory'], 0700);
    }
  }

  public function add_upload($deletion_date, $files) {
    $accept = true;

    // TODO: check for spammer

    // Generate a new ID
    $id = $this->db->generate_id();

    // Create the upload
    $upload = new Upload($id, $deletion_date,
                         $this->config['uploads_directory']);

    // Associate each file to the upload
    foreach ($files['error'] as $key => $error) {
      if ($error == UPLOAD_ERR_OK) {
        $temp = $files['tmp_name'][$key];
        $name = $files['name'][$key];

        // Check that the file type is allowed
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($info, $temp);
        if (!in_array($type, $this->config['allowed_file_types'])) {
          $accept = false;
          $return = array('error' => 'Error unauthorized MIME type ('.$type.
                                     ') for '.$name.'.<br />Please check the '.
                                     'list of authorized MIME types.');
          break;
        }
        finfo_close($info);

        $file = new File($upload, $name);
        $file->save($temp);

        $upload->add_file($file);
      }
    }

    if (!$accept) {
      // All files have been rejected
      $upload->delete();
    } else {
      // Save the upload
      $this->db->save_upload($upload);
      // Throw the upload's ID for the Javascript
      $return = array('success' =>  $upload->get_id());
    }

    print json_encode($return);
  }

  public function show_upload($id) {
    $id = SQLite3::escapeString($id);
    $upload = $this->db->get_upload_from_id($id);

    if ($upload === false) {
      error_upload_does_not_exist();
    } else {
      print '<h3>Available files to download <span class="label label-default">';
      print count($upload->get_files());
      print '</span></h3>';
      print '<div class="list-group">';

      foreach ($upload->get_files() as $file) {
        print '<div class="list-group-item clearfix">';
        print '<span class="glyphicon glyphicon-file"></span>&nbsp;';
        print '<span class="list-group-item-text">';
        print $file->get_name();
        print '</span>';
        print '<span class="list-group-item-btn-dl">';
        print '<a href="./'.$upload->get_id().'/'.$file->get_name();
        print '" class="btn btn-success" role="button">';
        print '<span class="glyphicon glyphicon-save"></span></a>';
        print '</span>';
        print '</div>';
      }

      print '</div>';
      print '<div class="row">';
      print '<div class="col-xs-12 col-sm-6 col-md-8">';
      print '<span class="glyphicon glyphicon-time"></span>&nbsp;';
      print 'Files are available until:';
      print '</div>';
      print '<div class="col-xs-6 col-md-4">';
      print '<span class="label label-warning">';
      print date('d/m/Y - h:i:s A', $upload->get_deletion_date());
      print '</span>';
      print '</div>';
      print '</div>';
    }
  }

  public function send_file($id, $filename) {
    $id = SQLite3::escapeString($id);
    $upload = $this->db->get_upload_from_id($id);

    if ($upload === false) {
      error_upload_does_not_exist();
    } else {
      $file = $upload->get_file_by_name($filename);

      if ($file === false) {
        error_file_does_not_exist();
      } else {
        $path = $file->get_path();

        if (isset($path) && file_exists($path)) {
          $info = finfo_open(FILEINFO_MIME_TYPE);
          $type = finfo_file($info, $path);
          finfo_close($info);

          header('Content-Type: '.$type);
          header('Expires: 0');
          header('Content-Length: '.filesize($path));

          $handle = fopen($path, 'r');
          fpassthru($handle);
          fclose($handle);
        }
      }
    }
  }

  public function render_top() {
    print '<!DOCTYPE html>';
    print '<html lang="en">';
    print '<head>';
    print '<meta charset="utf-8" />';
    print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    print '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    print '<title>'.$this->config['title'].'</title>';
    print '<link href="libs/bootstrap-3.2.0/css/bootstrap.min.css" rel="stylesheet" />';
    if ($this->config['bootstrap_theme']) {
      print '<link href="libs/bootstrap-3.2.0/css/bootstrap-theme.min.css" rel="stylesheet" />';
    }
    print '<link href="libs/fileinput/css/fileinput.min.css" rel="stylesheet" />';
    print '<link href="css/style.css" rel="stylesheet" />';
    print '</head>';
    print '<body>';
    print '<div class="container">';
    print '<div class="header">';
    print '<h1>'.$this->config['title'].'</h1>';
    print '</div>';
    print '<div class="alert alert-danger alert-dismissable" style="display: none;" id="error">';
    print '<button type="button" class="close" aria-hidden="true">&times;</button>';
    print '<strong>Error!</strong>&nbsp;<span id="error-text"></span>';
    print '</div>';
  }

  public function render_upload_form() {
    print '<div class="clearfix">';
    print '<div class="pull-left">';
    print $this->config['description'];
    print '</div>';
    print '<div class="pull-right">';
    print '<button type="button" class="btn btn-info popover-dismiss" data-toggle="popover" title="List of allowed MIME types" data-html=true data-content="<ul>';
    foreach ($this->config['allowed_file_types'] as $type) {
      print '<li>'.$type.'</li>';
    }
    print '</ul>"><span class="glyphicon glyphicon-flag"></span>&nbsp;Accepted Files</button>';
    print '</div>';
    print '</div>';
    print '<form enctype="multipart/form-data" action="." method="post">';
    print '<div class="form-group">';
    print '<label for="expiration">Expiration</label>';
    print '<select class="form-control" id="expiration" name="expiration">';
    print '<option value="600">10 minutes</option>';
    print '<option value="3600">1 hour</option>';
    print '<option value="86400" selected="selected">1 day</option>';
    print '<option value="604800">1 week</option>';
    print '<option value="2629743">1 month</option>';
    print '<option value="-1">Eternal</option>';
    print '</select>';
    print '</div>';
    print '<div class="form-group">';
    print '<label for="files">Select Files</label>';
    print '<input id="files" name="files[]" type="file" multiple="true" />';
    print '</div>';
    print '<input type="text" class="hidden" name="dontlook" placeholder="Do not look!" />';
    print '</form>';
    print '<div class="loading hide">';
    print '<div class="progress">';
    print '<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 0%" id="progress">';
    print '</div>';
    print '</div>';
    print '</div>';
  }

  public function render_bottom() {
    print '<div class="footer">';
    print '</div>';
    print '</div>';
    print '</body>';
    print '<script src="js/jquery-2.1.1.min.js"></script>';
    print '<script src="js/jquery.form.min.js"></script>';
    print '<script src="libs/bootstrap-3.2.0/js/bootstrap.min.js"></script>';
    print '<script src="libs/fileinput/js/fileinput.min.js"></script>';
    print '<script src="js/uploader.js"></script>';
    print '</html>';
  }

  public function cron() {
    $this->db->delete_old_uploads();
  }
}

$uploader = new Uploader($config);

// Launched from CLI, cron cleanup
if (php_sapi_name() == 'cli') {
  $uploader->cron();
  exit();
}

if (isset($_FILES) && !empty($_FILES) && isset($_POST['expiration'])) {
  $uploader->add_upload($_POST['expiration'], $_FILES['files']);
} else {
  $uri = explode('/', $_SERVER['REQUEST_URI']);

  if (isset($uri[3]) && (strlen($uri[3]) > 0)) {
    $uploader->send_file($uri[2], $uri[3]);
  } else {
    $uploader->render_top();

    if (isset($uri[2]) && (strlen($uri[2]) === 6)) {
      $uploader->show_upload($uri[2]);
    } else {
      $uploader->render_upload_form();
    }

    $uploader->render_bottom();
  }
}

// End of index.php
