<?php
require_once 'framework/Controller.php';
require_once 'framework/View.php';
require_once 'utils/Utils.php';
require_once 'model/User.php';
require_once 'model/Book.php';
require_once 'model/Rental.php';

// Utilisation de MyController pour injecter des

abstract class MyController extends Controller {
    public $mavar = 'salut';

}