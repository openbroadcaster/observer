<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Attribute\Depends;
use Codeception\Util\Locator;

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

    /**
     * @depends Tests\Acceptance\LoginCest:login
     */
    public function createPlayer(AcceptanceTester $I)
    {
        $I->click('#layout_main button.add');

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

    /**
     * @depends createPlayer
     */
    public function editPlayer(AcceptanceTester $I)
    {
        $I->waitForText('Test Player', 5, '#player_list');
        $I->click(Locator::elementAt('#player_list button.edit', 1));

        $I->waitForText('Player Settings', 5, 'legend');
        $I->wait(2); // fields filled out asynchronously after opening modal
        $I->fillField('#player_settings_description', 'Test Player Description');
        $I->uncheckOption('#player_settings_support_video');
        $I->uncheckOption('#player_settings_support_linein');
        $I->click('#layout_modal_window .add');

        $I->waitForText('Test Player Description', 5, '#player_list');
        $I->click(Locator::elementAt('#player_list button.edit', 1));

        $I->waitForText('Player Settings', 5, 'legend');
        $I->wait(2); // fields filled out asynchronously after opening modal
        $I->seeCheckBoxIsChecked('#player_settings_support_audio');
        $I->seeCheckBoxIsChecked('#player_settings_support_image');
        $I->dontSeeCheckboxIsChecked('#player_settings_support_video');
        $I->dontSeeCheckBoxIsChecked('#player_settings_support_linein');
    }

    /**
     * @depends editPlayer
     */
    public function deletePlayer(AcceptanceTester $I)
    {
        $I->waitForText('Test Player Description', 5, '#player_list');
        $I->click(Locator::elementAt('#player_list button.edit', 1));

        $I->waitForText('Player Settings', 5, 'legend');
        $I->click('#layout_modal_window button.delete');
        $I->waitForText('Are you sure you want to remove this player?', 5);
        $I->click('#confirm_button_okay');

        $I->wait(2);
        $I->dontSee('Test Player Description', '#player_list');
    }

    /**
     * @depends deletePlayer
     */
    public function dependsPlayers(AcceptanceTester $I)
    {
        $I->click('#layout_main button.add');

        $I->waitForText('Player Settings', 5, 'legend');
        $I->fillField('#player_settings_name', 'Player A');
        $I->fillField('#player_settings_password', 'abc123');
        $I->checkOption('#player_settings_support_audio');
        $I->checkOption('#player_settings_support_image');
        $I->checkOption('#player_settings_support_video');
        $I->checkOption('#player_settings_support_linein');
        $I->click('#layout_modal_window .add');

        $I->waitForText('Player A', 5, '#player_list');
        $I->click('#layout_main button.add');

        $I->waitForText('Player Settings', 5, 'legend');
        $I->fillField('#player_settings_name', 'Player B');
        $I->fillField('#player_settings_password', 'xyz789');
        $I->checkOption('#player_settings_support_audio');
        $I->checkOption('#player_settings_support_image');
        $I->checkOption('#player_settings_support_video');
        $I->checkOption('#player_settings_support_linein');
        $I->click('#layout_modal_window .add');

        $I->waitForText('Player B', 5, '#player_list');
    }
}
