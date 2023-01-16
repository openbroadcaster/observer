<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Attribute\Depends;

class LoginCest extends BaseCest
{
    public function login(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);
    }

    /**
     * @depends login
     */
    public function logout(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);

        $I->moveMouseOver('#obmenu li[data-slug="account"]');
        $I->waitForElement('#obmenu li[data-slug="logout"]', 5);
        $I->click('Logout', '#obmenu li[data-slug="logout"]');

        $I->waitForText('Welcome to OpenBroadcaster', 5);
    }
}
