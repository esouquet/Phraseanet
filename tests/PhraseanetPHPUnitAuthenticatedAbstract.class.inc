<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetPHPUnitAuthenticatedAbstract extends PhraseanetPHPUnitAbstract
{

    public function setUp()
    {
        parent::setUp();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $auth = new Session_Authentication_None(self::$user);
        $session->authenticate($auth);
    }

    public function tearDown()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $session->logout();
        parent::tearDown();
    }
}
