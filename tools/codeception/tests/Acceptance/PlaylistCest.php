<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Attribute\Depends;
use Codeception\Util\Locator;

class PlaylistCest extends BaseCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/welcome');
        $I->fillField('#login_username', $this->username);
        $I->fillField('#login_password', $this->password);
        $I->click('#login_submit');
        $I->waitForText('Logged in as: ' . $this->username, 10);
    }

    /**
     * @depends Tests\Acceptance\MediaCest:uploadMusic
     * @depends Tests\Acceptance\MediaCest:uploadVideo
     * @depends Tests\Acceptance\MediaCest:uploadImage
     */
    public function createPlaylist(AcceptanceTester $I)
    {
        $I->waitForElement('#sidebar_search_tab_playlist', 10);
        $I->click('Playlists', '#sidebar_search_tab_playlist');

        // Wait for specific context since just checking for 'New' means we
        // might accidentally click the new media button instead.
        $I->waitForText('New', 5, '#sidebar_search_playlist_buttons');
        $I->click('New', '#sidebar_search_playlist_buttons');

        $I->waitForText('New Playlist', 5, '#playlist_edit_heading');
        $I->fillField('#playlist_name_input', 'Test Playlist');
        $I->fillField('#playlist_description_input', 'This is a testing playlist created by Codeception.');

        $I->waitForElement('#playlist_users_permissions_input select', 5);
        $option = $I->grabTextFrom('#playlist_users_permissions_input select option:last-child');
        $I->selectOption('#playlist_users_permissions_input select', $option);

        $I->click('Media', '#sidebar_search_tab_media');
        $I->waitForElement('tr[data-title="Electric Scooter Video"]', 5);
        $I->dragAndDrop('tr[data-title="Electric Scooter Video"]', '#playlist_items');
        $I->waitForText('Acceptance Test Artist - Electric Scooter Video', 5);

        $I->waitForElement('tr[data-title="Apollo 11 Image"]', 5);
        $I->dragAndDrop('tr[data-title="Apollo 11 Image"]', '#playlist_items');
        $I->waitForText('Acceptance Test Artist - Apollo 11 Image', 5);

        // There are multiple save buttons for some reason, this makes Codeception
        // complain about the element not being interactable, so fix it with this.
        //
        // Also Codeception won't allow :first, so we're relying on its built-in
        // Locator functionality.
        $I->click(Locator::elementAt('#playlist_edit_standard_container button.add', 1));
        $I->waitForText('Playlist saved.', 5, '#playlist_addedit_message');
    }
}
