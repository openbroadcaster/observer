<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class PlayerManagerCest extends BaseCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);

        $I->moveMouseOver('#obmenu li[data-slug="admin"]');
        $I->waitForElement('#obmenu li[data-slug="player_settings"]', 5);
        $I->click('Player Manager', '#obmenu li[data-slug="player_settings"]');
        $I->waitForText('Player Manager', 5, '#layout_main');
    }

    public function createPlayer(AcceptanceTester $I)
    {
        $I->click('New', '#layout_main');

        $I->waitForText('Player Settings', 5, 'legend');
        $I->fillField('#player_settings_name', 'Test Player');
        $I->fillField('#player_settings_password', 'abc123');
        $I->checkOption('#player_settings_support_audio');
        $I->checkOption('#player_settings_support_image');
        $I->checkOption('#player_settings_support_video');
        $I->checkOption('#player_settings_support_linein');
        $I->click('#layout_modal_window .add');

        $I->waitForText('Test Player', 5, '#player_list');
    }
}
