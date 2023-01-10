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
     * @depends Tests\Acceptance\LoginCest:login
     * @depends Tests\Acceptance\MediaCest:uploadMusic
     * @depends Tests\Acceptance\MediaCest:uploadVideo
     * @depends Tests\Acceptance\MediaCest:uploadImage
     */
    public function createPlaylist(AcceptanceTester $I)
    {
        // CREATE PLAYLIST

        $I->waitForElement('#sidebar_search_tab_playlist', 10);
        $I->click('Playlists', '#sidebar_search_tab_playlist');

        // Wait for specific context since just checking for 'New' means we
        // might accidentally click the new media button instead.
        $I->waitForText('New', 5, '#sidebar_search_playlist_buttons');
        $I->click('New', '#sidebar_search_playlist_buttons');

        $I->waitForText('New Playlist', 5, '#playlist_edit_heading');
        $I->fillField('#playlist_name_input', 'Test Playlist');
        $I->fillField('#playlist_description_input', 'This is a testing playlist created by Codeception.');

        // Add user permission (last one in list).
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

        // CHECK PLAYLIST DETAILS

        $I->click('Playlists', '#sidebar_search_tab_playlist');
        $I->waitForElement('tr[data-name="Test Playlist"]', 10);
        $I->doubleClick('tr[data-name="Test Playlist"]');

        $I->waitForText('Playlist Details', 5);
        $I->waitForText('Test Playlist', 5, '#playlist_details_name');
        $I->waitForText('This is a testing playlist created by Codeception', 5, '#playlist_details_description');
        $I->waitForText($this->username, 5, '#playlist_details_owner');

        $I->click('Items', '#layout_main ob-tab-select');
        $I->waitForText('Playlist Items', 5);
        $I->waitForText('Acceptance Test Artist - Electric Scooter Video', 5, '#playlist_details_items_table');
        $I->waitForText('Acceptance Test Artist - Apollo 11 Image', 5, '#playlist_details_items_table');
        $I->waitForText('00:27', 5, '#playlist_details_items_table');

        $I->click('Where Used', '#layout_main ob-tab-select');
        $I->waitForText('Playlist is not in use.', 5, '#playlist_details_used');
    }

    /**
     * @depends createPlaylist
     */
    public function editPlaylist(AcceptanceTester $I)
    {
        // EDIT PLAYLIST

        $I->waitForElement('#sidebar_search_tab_playlist', 10);
        $I->click('Playlists', '#sidebar_search_tab_playlist');

        $I->waitForElement('tr[data-name="Test Playlist"]', 10);
        $I->doubleClick('tr[data-name="Test Playlist"]');

        $I->waitForText('Playlist Details', 5);
        $I->click('#playlist_details_edit');

        $I->waitForText('Edit Playlist', 5, '#playlist_edit_heading');
        $I->fillField('#playlist_name_input', 'Test Playlist UPDATE');
        $I->fillField('#playlist_description_input', 'Updated playlist description for testing.');

        // Remove user permission.
        $I->waitForElement('#playlist_users_permissions_input ob-user span', 5);
        $I->click('#playlist_users_permissions_input ob-user span');

        // Add group permission.
        $option = $I->grabTextFrom('#playlist_groups_permissions_input select option:last-child');
        $I->selectOption('#playlist_groups_permissions_input select', $option);

        // Remove first media item from playlist.
        $I->click(Locator::elementAt('#playlist_items .playlist_addedit_item', 1));
        $I->pressKey('body', \Facebook\WebDriver\WebDriverKeys::DELETE);

        // Again there are multiple save buttons which makes Codeception grumpy
        // so just grabbing the first (they all do the same JS anyway) with a
        // custom locator it is.
        $I->click(Locator::elementAt('#playlist_edit_standard_container .playlist_data_save button.add', 1));

        // CHECK PLAYLIST DETAILS

        $I->waitForElement('tr[data-name="Test Playlist UPDATE"]', 5);
        $I->doubleClick('tr[data-name="Test Playlist UPDATE"]');

        $I->waitForText('Playlist Details', 5);
        $I->waitForText('Test Playlist UPDATE', 5, '#playlist_details_name');
        $I->waitForText('Updated playlist description for testing.', 5, '#playlist_details_description');
        $I->waitForText($this->username, 5, '#playlist_details_owner');

        $I->click('Items', '#layout_main ob-tab-select');
        $I->waitForText('Playlist Items', 5);
        $I->waitForText('Acceptance Test Artist - Apollo 11 Image', 5, '#playlist_details_items_table');
        $I->waitForText('00:15', 5, '#playlist_details_items_table');
        // Already waited for other items, presumably at this point deleted item
        // should have been loaded if it wasn't deleted from the list.
        $I->dontSee('Acceptance Test Artist - Electric Scooter Video', '#playlist_details_items_table');
    }

    /**
     * @depends editPlaylist
     */
    public function deletePlaylist(AcceptanceTester $I)
    {
        $I->waitForElement('#sidebar_search_tab_playlist', 10);
        $I->click('Playlists', '#sidebar_search_tab_playlist');

        $I->waitForElement('tr[data-name="Test Playlist UPDATE"]', 10);
        $I->doubleClick('tr[data-name="Test Playlist UPDATE"]');

        $I->waitForText('Playlist Details', 5);
        $I->click('#playlist_details_delete');

        $I->waitForText('Delete Playlists', 5);
        $I->click('Yes, Delete');
        $I->waitForText('Playlists have been deleted', 5);
    }
}
