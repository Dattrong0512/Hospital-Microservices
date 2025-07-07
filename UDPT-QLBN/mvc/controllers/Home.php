<?php

// http://localhost/live/Home/Show/1/2

require_once("./mvc/core/Controller.php");
class Home extends Controller
{
  function Show()
  {
    $view = $this->view("layouts/main",["Page" => "Home"]);
  }
}
