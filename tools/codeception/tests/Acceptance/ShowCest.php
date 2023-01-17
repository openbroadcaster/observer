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
     *
     * TODO: Generic media and playlist dependencies to test with instead.
     */
    public function createShow(AcceptanceTester $I)
    {
        $I->waitForText('Player A', 5, '#schedule_player_select');
        $I->dragAndDrop('tr[data-title="Electric Scooter Video"]', '#schedule_container');
        $I->waitForText('Acceptance Test Artist - Electric Scooter Video', 5, '#show_item_info');

        $start = date('Y-m-d H:i:s');
        $I->fillField('#show_start_datetime input', $start);
        $I->fillField('#show_duration input', '2:00:00');
        $I->click('#layout_modal_window button.add');

        $I->waitForText('Electric Scooter Video', 5, '#schedule');
        $I->moveMouseOver(Locator::elementAt('.schedule_datablock', 1));
        $I->waitForText('Electric Scooter Video', 5, '#schedule_details');
        $I->waitForText($this->username, 5, '#schedule_details');
        $I->waitForText($start, 5, '#schedule_details');
        $I->waitForText('2:00:00', 5, '#schedule_details');
    }

    /**
     * @depends createShow
     */
    public function editShow(AcceptanceTester $I)
    {
        $I->waitForText('Electric Scooter Video', 5, '#schedule');
        $I->doubleClick(Locator::elementAt('.schedule_datablock', 1));
        $I->waitForText('Acceptance Test Artist - Electric Scooter Video', 5, '#show_item_info');
        $I->selectOption('#show_mode', 'xdays');
        $I->waitForElement('#show_x_data', 5);
        $I->fillField('#show_x_data', '2');

        $end = date('Y-m-d', strtotime('+2 weeks'));
        $I->fillField('#show_stop_date input', $end);
        $I->click('#layout_modal_window button.add');

        $I->wait(2); // give shows time to update (not sure what element to check for)
        // Since the shows table shows a week at a time, when there's a show every
        // two days in this test that means the table can show either 3 or 4 data
        // blocks.
        $I->seeNumberOfElements('.schedule_datablock', [3,4]);
    }

    /**
     * @depends editShow
     */
    public function deleteShow(AcceptanceTester $I)
    {
        $I->waitForText('Electric Scooter Video', 5, '#schedule');
        $I->doubleClick(Locator::elementAt('.schedule_datablock', 1));
        $I->waitForText('Acceptance Test Artist - Electric Scooter Video', 5, '#show_item_info');

        $I->click('#item_properties_delete');
        $I->waitForText('Delete this show?', 5, '#confirm_message');
        $I->click('Yes, Delete');
        $I->wait(2);
        $I->dontSeeElement('.schedule_datablock');
    }
}
