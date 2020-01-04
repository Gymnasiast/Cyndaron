<?php


namespace Cyndaron\User;

use Cyndaron\DBConnection;
use Cyndaron\Page;
use Cyndaron\Widget\Modal;
use Cyndaron\Widget\Toolbar;

class UserManagerPage extends Page
{
    const USER_LEVEL_DESCRIPTIONS = [
        'Niet ingelogd',
        'Normale gebruiker',
        'Gereserveerd',
        'Gereserveerd',
        'Beheerder',
    ];

    public function __construct()
    {
        parent::__construct('Gebruikersbeheer');
        $this->addScript('/src/User/UserManagerPage.js');
        $this->render([
            'users' => User::fetchAll([], [], 'ORDER BY username'),
            'userLevelDescriptions' => self::USER_LEVEL_DESCRIPTIONS,
        ]);
    }
}