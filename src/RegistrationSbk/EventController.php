<?php
declare (strict_types = 1);

namespace Cyndaron\RegistrationSbk;

use Cyndaron\Controller;
use Cyndaron\Request;
use Cyndaron\User\UserLevel;

class EventController extends Controller
{
    protected $getRoutes = [
        'register' => ['level' => UserLevel::ANONYMOUS, 'function' => 'register'],
        'viewRegistrations' => ['level' => UserLevel::ADMIN, 'function' => 'viewRegistrations'],
    ];

    protected function register()
    {
        $id = intval(Request::getVar(2));
        $event = Event::loadFromDatabase($id);
        new RegisterPage($event);
    }

    protected function viewRegistrations()
    {
        $id = intval(Request::getVar(2));
        $event = Event::loadFromDatabase($id);
        new EventOrderOverviewPage($event);
    }
}