OpenBroadcaster Test Process

# Assumptions

* Fresh OB installation, updated to latest version.
* Admin user created with username and password provided to testing framework (see exceptions).
* Codeception with WebDriver and Selenium for testing.

# Overview

We can split the testing process into three separate steps: the UI, the API, and the exceptions.

Parts of OpenBroadcaster are best tested by interacting with the UI directly. This includes such things as uploading and editing media files, scheduling shows, and managing users or permissions. While all of these can be directly interacted with using the API, this wouldn't account for the many client-side issues that could pop up. By going through the process step by step and making the test framework expect to see certain results, we can ensure that updates don't break important UI functionality.

[NOTE: have to test how well this works in practice] Interacting with the API can be done with Codeception as well. It allows for executing JS directly in the browser, meaning we can use OpenBroadcaster's `OB.API.post` function to call controllers directly and check their results. This is best used for all sorts of small sanity checks, making sure that updates don't break core functionality going on in the background. Getting and setting data in various controllers are all tests that should be very straightforward, and allow us to quickly catch if something breaks.

There is likely to be some overlap between UI and controller testing, but this shouldn't be a problem. By keeping tests written directly for the API small, the time investment shouldn't be too bad - it's the UI testing where edge-cases become trickier. Either way UI test coverage takes priority, as those are the 'real world' situations, so to speak.

Finally there's the exceptions. These include cases such as creating new users: functionality where something has to be done *outside* of the website itself to continue. There are workarounds for this, but it's important to note them since they probably won't take priority for creating tests.

TODO: Probably remove API testing through JS, but add some way to test for code coverage.

# Testing Processes

## UI

Unless stated otherwise, it is important that all tests be done in the order specified.

### Login

*Note: This test is a dependency for all further UI testing.*

1. Login
2. Logout

### Account Settings

1. Update password.
2. Logout.
3. Login with new password.
4. Update password back to old one and go through previous steps (even if tests are supposed to start from a clean installation, having the user stop working on each attempt would be frustrating).
5. Create App Key and confirm its creation.
6. Rename App Key.
7. Change various user interface settings.
8. Save page.
7. Reload page and confirm settings and App Key name have been changed.

### Media

1. Upload media file, create media item
2. Get media item, verify data is as specified on creation
3. Edit media item
4. Verify media item data is as specified on edit
5. Archive media item
6. Verify media item is archived
7. Delete media item
8. Verify media item is deleted
9. Recreate media items for further testing.

TODO Where Used.
TODO Restore archived version.
TODO Versions.

### Playlist

*Dependency: Media tests all passed.*

1. Create new playlist, include media items
2. Double-click playlist in sidebar, confirm details / items.
3. Edit playlist, update a few settings, remove and add a media item, then save.
4. Double-click playlist in sidebar, confirm details / items updated.
5. Delete playlist.
6. Confirm playlist no longer in sidebar.
7. Recreate playlists for further testing.

TODO Dynamic selections and station IDs? I have no idea how those work, please let me know.
TODO Different playlist types, LiveAssist etc
TODO 'Where Used' in playlist details. Part of shows/timeslots? Make part of those tests, have to make sure a few playlists etc are added.

### Sidebar

*Dependency: Media and Playlist tests all passed.*

TODO (Searches and such)

### Shows

*Dependency: Media, Playlist, and Player Manager tests all passed.*

1. Switch to second player, refresh page, go to shows, confirm on second player.
2. Drag media item to show, change duration, confirm they show up.
3. Same as above with repeating item.
4. Update items, confirm changed.
5. Delete items.
6. Repeat steps 2 to 5 for playlists.

### Timeslots

*Dependency: Media, Playlist, and Player Manager tests all passed.*

TODO

### Alerts

*Dependency: Media and Player Manager tests all passed.*

TODO

### Client Settings

TODO

### Player Manager

*Note: Missing tests that involve testing against a working player.*

1. Create new player, confirm created.
2. Edit player settings and save, confirm updated on check.
3. Delete player, confirm deleted.
4. Create players for other tests.

TODO: Default playlists, station IDs

### Player Monitoring

*Note: Missing tests that involve testing against a working player.*

TODO

### Media Settings

TODO

### Modules

TODO

### Permissions

TODO

### User Management

TODO

### Cleanup

TODO: While testing process assumes clean install with one admin user, it'd still be very nice to be able to run the testing process a couple times in a row without having to do a whole db reset, especially with tests likely to leave a bit of a mess behind (that then interferes with those same tests when they run a second time). What's the most straightforward way of doing this? 

## API

TODO (All controllers that are straightforwardly interactable, prioritize UI testing)

## Exceptions / Hard To Test

* Creating new users: this involves interacting with confirmation emails. While this could in theory be done (either with email or by interacting with the database directly from the testing framework in some way), it may not be worth prioritizing at this point.
* Changing email on account settings.
* Testing against real players.
