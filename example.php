<?php

include "View.php";

$view = new View();

$view->assign("htmlcode", "<a href=\"http://google.com\">some <i>html</i> code</a>");
$view->assign("name", "Waldo");
$view->assign("numbers", array(5, 7, 1, 4));

$view->display("example");