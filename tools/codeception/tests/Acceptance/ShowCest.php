<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Attribute\Depends;
use Codeception\Util\Locator;

class ShowCest extends BaseCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);

        $I->moveMouseOver('#obmenu li[data-slug="schedules"]');
        $I->waitForElement('#obmenu li[data-slug="shows"]', 5);
        $I->click('Shows', '#obmenu li[data-slug="shows"]');
        $I->waitForText('Shows', 5, '#layout_main');
    }

    /**
     * @depends Tests\Acceptance\LoginCest:login
     * @depends Tests\Acceptance\PlayerManagerCest:dependsPlayers
     * @depends Tests\Acceptance\MediaCest:uploadVideo
     * @depends Tests\Acceptance\MediaCest:uploadImage
     */
    public function createShow(AcceptanceTester $I)
    {
        // TODO
        $I->see('Shows');
    }
}
