<?php
require '../config.php';
session_destroy();
redirect('/livraison_locale/index.php');
