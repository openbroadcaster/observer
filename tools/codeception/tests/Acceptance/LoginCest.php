<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class LoginCest extends BaseCest
{
    public function login(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);
        $I->see('Logged in as: ' . $this->username);
    }
}
