<?php

/*
 *
 */

namespace Bloglist\Controllers;

class RequestParams
{
  public function getParam($name) {
    $val = "";
    if (isset($_REQUEST) && isset($_REQUEST[$name])) {
      $val = $_REQUEST[$name];
    }
    return trim($val);
  }
}
