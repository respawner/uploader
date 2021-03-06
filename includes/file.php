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

require_once 'upload.php';

/**
 * This class represents a file that is included in an upload.
 *
 * A file is defined by the upload that it is part of and the name of the
 * file. From these two characteristics the code will be able to locate the
 * file on the files system.
 */
final class File {
  /**
   * The upload that this file is part of.
   */
  private $upload;

  /**
   * The real name of the file (and not its full path).
   */
  private $name;

  /**
   * The MIME type of the file.
   */
  private $mime;

  /**
   * The size of the file in octets.
   */
  private $size;

  /**
   * Build a new File object with the given parameters.
   *
   * @param Upload  $upload the upload that this file is part of.
   * @param string  $name   the name of this file.
   * @param string  $mime   the MIME type of this file.
   * @param integer $size   the size of this file.
   */
  public function __construct($upload, $name, $mime, $size) {
    $this->upload = $upload;
    $this->name = $name;
    $this->mime = $mime;
    $this->size = $size;
  }

  /**
   * Get the upload that this file is part of.
   *
   * @return Upload the upload.
   */
  public function get_upload() {
    return $this->upload;
  }

  /**
   * Get the name of this file.
   *
   * @return string the filename.
   */
  public function get_name() {
    return $this->name;
  }

  /**
   * Get the MIME type of this file.
   *
   * @return string the MIME type.
   */
  public function get_mime_type() {
    return $this->mime;
  }

  /**
   * Get the size of this file.
   *
   * @return integer the size in octets.
   */
  public function get_size() {
    return $this->size;
  }

  /**
   * Get the location of this file on the files system.
   *
   * @return string the path to the file.
   */
  public function get_path() {
    return $this->upload->get_path().'/'.$this->name;
  }

  /**
   * Save the file on the files system right after its upload is completed.
   *
   * @param string $tmp the temporary file which has been uploaded.
   */
  public function save($tmp) {
    if (is_uploaded_file($tmp)) {
      move_uploaded_file($tmp, $this->get_path());
    }
  }

  /**
   * Remove the file from the files system.
   */
  public function delete() {
    unlink($this->get_path());
  }
}

// End of file.php
