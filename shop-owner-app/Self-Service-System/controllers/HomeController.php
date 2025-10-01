<?php
require_once 'views/home.php';

class HomeController
{
    public function index()
    {
        // Just render the view
        renderHome();
    }
}
