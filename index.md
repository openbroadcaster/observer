---
layout: default
title: Server
---
# Media Server 
{:.no_toc}

 * TOC
{:toc}

## Getting Around 
{:toc}

The main screen comprises three areas: a center 'work' area, media sidebar, and a menu panel.

![Server Main Screen](/server/img/mainscreen.jpg ){: .screenshot} 

<a name="cwa"></a>
The __centre work area__ is the main window workspace.  This is where users can create play lists, add and edit media, schedule shows, update system settings, install modules and manage users.  In the example above, a *Schedule* is loaded into the main window workspace.

To the right of the center 'work' area is the __sidebar__ , comprising two tabs, `Media` and `Playlists`, as well as a `Preview` window. Users can toggle between tabs and perform dynamic searches to filter a list of items based on a metadata query. One or more items from the list may be selected. Actions triggered by clicking on buttons below the list operate on selected items. Additional functions  are available by right clicking on items in the list.  To preview items (any supported media including audio, image or video), simply drag and drop any media into the `Preview` window above the sidebar tabs. At the bottom of the sidebar, the number of items in the (filtered) list is shown.

Along the bottom of the screen is the __menu panel__. Depending on user permissions some menus will remain hidden.

### Profile and Account ###

![User Account Settings](/server/img/account-settings.png ){: .screenshot}

Login to the [OpenBroadcaster Server] application as the `admin` user (default password is 'admin')

Here you are able to custom tailor your session. Using the `account` menu to access the `admin` account settings.  Some of the things you can change on a per user basis.

1. Change your display name
1. Update password
1. Set `Email`
1. Set the number of search results to display in media sidebar
1. Select a `Theme`
1. Enable Dyslexic Font making it easier to read and comprehend for some users
1. `Sidebar Display`to appear from Left or Right

### Language

User menus, form fields and system messages are displayed in English by default. Available language options are displayed in a dropdown menu. These settings are __not__ system-wide. Each user may choose their own language settings. Select the desired language of menus, help and tool tips. 

Save and refresh browser.

### Accessibly Themes

Background/foreground color and font options are designed to enhance accessibility of the interface using bright/dark contrast settings or using dyslexia friendly fonts. These settings are __not__ system-wide. Each user may choose their own theme and font settings.

Select desired `Themes` ranging from high contrast, light\dark backgrounds. Tanzanite is the Default theme. 

Save and refresh refresh your browser to take effect.

<br/>

## Media

OpenBroadcaster allows media of different types (audio, video, images) to be managed within a single catalogue or media library.  Using radio buttons admins can select which formats the system will accept and recognize.  If media is tried to be uploaded that isn't supported, then it will not be allowed to be added to the library.  Files are screened a number of ways for authenticity including MD5 checksum.  Renaming a text file to *.mp3 will not be accepted.

The `MY` button when selected filters to only show media created and uploaded by user.

### Media Formats

Compatible media codecs and containers are listed below:

|--
|Format|Description|File extension| 
|:-|:--|:-:|
|Audio Formats|||
|FLAC| Free lossless audio codec|.flac|
|MP3|MPEG-1 Layer-3 Audio ☼ |.mp3 |
|MP4|MPEG-4 Audio ☼ |.mp4 |
|Ogg Vorbis|Ogg Container Format |.ogg |
| WAV |Waveform Audio | .wav|
||||
|Video Formats|||
|AVI|Audio Video Interleave, Container Format |.avi|
|MOV|Apple Quicktime ☼ |.mov|
|MPEG| Moving Picture Experts Group ☼ |.mpg|
|Ogg Video|Ogg Container Format |.ogv|
|WMV|Windows Media Video ☼ |.wmv|
||||
|Image Formats|||
|JPEG|Joint Photographic Experts Group|.jpg|
|PNG|Portable Network Graphics|.png|
|SVG|Scalable Vector Graphics|.svg|
|--|--|--|

☼ Non-Free Proprietary - Licence Required

The System Administrator may choose to restrict uploads to a subset of the compatible formats. 

![Supported Media](/server/img/media-supported.png ){: .screenshot} 

Global default media fields and behaviour.

![Media Fields](/server/img/media-fields.png ){: .screenshot} 

Complete list of [Experimental](https://wiki.openbroadcaster.com/experimental) codecs and containers. 

<br/>

### Metadata 

![Upload Details Screen](/server/img/uploadDetails.jpg ){: .lowres} 

If available, ID3 data are automatically entered into the upload form, otherwise, enter the __Artist__ , __Title__ , along with other known metadata for each file. For metadata common to all items, the `Copy to All` button may be used to facilitate data entry. Use the __Category__ item to facilitate use of Station ID or Priority Broadcasts messages in [Playlists](#playlist).

Depending on permissions, user may or may not be able to set the *is media approved* flag. If not then the media will remain in an 'awaiting approval' queue until the Moderator approves it for inclusion in the music library. A *Status* of 'Public' will allow others to include the selection in their own Playlists. Private media will not be available for other users to browse. *Dynamic Selection* should be enabled for media such as music and station IDs, so that these items are accessible as Dynamic Playlist selections.

Once metadata have been entered, `Save` the items in the queue to add them the library. Incompatible file formats are flagged, and will not be processed in the upload queue.

### Uploading

![Media Upload Screen](/server/img/uploadMedia.jpg ){: .screenshot} 

New Media may be added to the library using the media uploader, accessed from the __media__ menu.

Drag and drop one or more media files to the uploader, or click within the shaded box to open a file selection window. Each file is uploaded in sequence, and added to a queue. Progress of the current upload is displayed alongside the filename.

__Pro Tip__ Max Limit set to 1GB 

<br/>

### Edit and Update

Select media from sidebar.  Either click `Edit` or right click `Edit`  Media info may be edited.

### Bulk Edit 

Batch Processing 

1. Selecting multiple Media items (CTRL + Click, Click + Shift) and Click 

1. Click `Edit` from media sidebar. Opens all selected media.

1. Use `Copy All` <i class="icon-copy"></i> icon beside the field you wish to copy to all.

<a name="search"></a>

### Media Settings

#### Categories and Genres

![Add Genre](/server/img/Add_Genre.png ){: .screenshot} 

Create and edit unlimited categories and associated genres of media. 
e.g.  Audio-Inuktitut, Images-Unicorns, Video-28mmPathe

Each `Category` and `Genre` can be specificied as default

<br/>

### View Details 

![Media Details](/server/img/media-details.png ){: .screenshot}

From the `Media Sidebar`, hightlight, right click and select `Details` to find out where the PL is used, who created it and when it was last modified.

Displays info This is where the 

- Who uploaded and when

- `Media ID` can be found including usage of media

- Where it is being used

<br/>
### Language and Country

Use drop down menus to select applicable `Language` and `Country`

### Media Field Settings

![Media Field Settings](/server/img/media-field-settings.png){: .screenshot} 

Default Settings and mandatory fields can be applied globally

<br/>

## Search

__Sidebar__ tabs  used to search the media library or Playlist catalogue. Items are filtered to displayed only items to which the current user has permission, those which are marked as approved(`ap`), or further restricted to include only those media owned(`my`) by that user. With additional privileges, searches may also be conducted on archived(`ar`) or unapproved(`un`) media. Deleted media items go into a special directory called Archive.  Authorized users are able to go into this archive, purge delete the actual media from the file system, or restore the media back to the library. The active filter is indicated by the use of uppercase notation (e.g. `AP`).

__Simple search mode__, users enter terms (i.e. a word or phrase) in the query window, application returns all media with title or artist containing those terms, or PlayLists with matching name/description. Click simple to clear or clear test and press enter 

### Advanced Searches

![Advanced Search Screen](/server/img/advancedSearch.jpg ){: .square_redux} 

__Advanced search mode__, users combine search criteria to query and filter large libraries. Query fields and search terms may be specified and added to a list of search criteria.

<br/>

![Advanced Search Results](/server/img/searchResults.jpg ){: .screenshot} 

Once a search is conducted, items matching all listed criteria will be returned and the number of results is displayed at the bottom of the search tab. Further sorting or returns can be done by clicking on the headers of columns on the list to display in “Ascending\Descending” order. To clear the search results, clear the search window and press enter. Use the previous and next buttons to display the next page of results.   The number of results to display per page may be set in the user profile settings. Select and reload previous search results using `my searches`.

<br/>

### Saved Searches

![Saved Searches](/server/img/Saved_Searches.png ){: .screenshot} 

Search history results are recalled and used edited again for personalized results.

<br/>

### Default Search View

May be saved and one can be set as default.  When a default search is set, results will be displayed when logging into your profile.

__Pro Tip__ Remembers the last search result queries to be modified or save 

<a name="playlist"></a>

## Playlists

A __Playlist__ groups selections from the media library for scheduled or recurring broadcast and may contain individual media selections or combinations of media may be combined in PlayLists.  Buttons along bottom `Add Dynamic Selection` and `Station ID` add functionality.

The `MY` button when selected filters to only show Play Lists created and managed by user.

### Types of PlayLists

#### __Standard_Playlist__ 

![Standard Playlist](/server/img/playlist_items.jpg ){: .screenshot}

A basic Playlist that you can add `media`,`dynamic segments`and`station IDs` Playlist(s) may be searched, saved and edited later.

<br/>

#### __Advanced Playlist__

<br/>

![Advanced Playlist](/server/img/adv_playlist.jpg ){: .screenshot} 

Mixes Image slide show with accompanying Audio.  Cuts into Video section. Audio and image data may be played simultaneously. Image media will be added to the list on the right of the Playlist items, audio tracks on the left. `zoom in/zoom out` on the schedule of Playlist items to increase the resolution of the time scale.  To create an Advanced Playlist containing `Dynamic Selections`, first create a __Standard__ Playlist containing Dynamic Selections, then add that Playlist to the Advanced Playlist.

<br/>

#### __LIVE Assist Playlist__

![LIVE Assist Playlist](/server/img/LA_Playlist.png ){: .screenshot} 

Used with LA Touch screen interface and hot button Player. Special Playlist that contains break points and button player.  Accepts incoming live RTP Streams

Runs on a touch screen computer for LIVE Radio operation accepting incoming audio streams.

<br/>

See [LIVE Assist QuickStart](/live-assist "LIVE Assist")

### Create Playlist
{:toc}

Using the `playlist->new playlist` menu option:

1. Provide a `Name` and `Description` for the Playlist so it can be easily identified.
1. Give your Playlist a `Status` of __public__ so other users can use your Playlist in their PlayLists and schedules (private PlayLists are only available to their owners).
1. Drag items from the media sidebar search results for media to the drop zone in the [centre work area](#cwa)
1. Click Save

### Edit Playlist

To Edit, Select Playlist and click `Edit` or right click, `Edit`

### Dynamic Segment

![Dynamic Playlist Segments](/server/img/Dynamic_Segments.png ){: .screenshot}

Plays a specified number of media as a segment.   Once a dynamic section is setup, additional media items added later that match the search filters will automatically be included as items to play.

1. Use a search to generate dynamic selections for a specified time segment. eg. Filter media on the right to show  __The Beatles__ 

1. Edit or create new Playlist.

1. At bottom of PL, click Add Dynamic Segments

1. Set number of `Dynamic Selections` to be drawn from the last search of the media library.

In image below we have set 3 items to play out of a search results of 254 items.  Automatically estimates the time of this segment.

`Station ID` Button adds a special segment that automatically inserts Station IDs assigned to the specific player.  This will play a `Station ID` into the Play List when it plays in a different station.
EG CFET Station IDs will play on CFET and the same Play List will play CJHJ Station IDs  when it plays on CJHJ.  Split Feed programming.

### Default Play List (DPL)

This Playlist is assigned to the `Player` to fill in when there is nothing scheduled.

<br/>

### View Details 

![Playlist Details](/server/img/PlayList_Details.png ){: .screenshot}

From the Playlist sidebar, right click and select `Details` to find out where the PL is used, who created it and when it was last modified.

<br/>

## Schedules

![Schedule Grid](/server/img/schedule_grid.jpg ){: .screenshot} 

Server schedule grid portrays a week of programming, with shaded, titled blocks indicating the content spanning those dedicated timeslots.  Hover the cursor over a program block to view a summary of the scheduled show. Double-clicking a block accesses Show editor, if the current user's permissions allow this.

<br/>

### Media and Shows

Schedule a single piece of media or a Playlist (Show) or External Line IN Audio source.

Duration is automatically calculated for individual media tracks and estimated for `dynamic segments`. 

DPL (Default Play List) assigned to that Player automatically starts to avoid dead air filling gaps with dynamic music segments. eg. 60 minute slot containing 50 minutes of programming, DPL will fill to top of the hour for next Time slot.

Flexible methods to schedule media and shows listed in ease of task.

1. Upload media. Drag to schedule and set to play in a time slot.

1. Select uploaded media and drag into an existing scheduled Playlist.

1. Priority function to play every N seconds.  Set start and stop dates. Use sparingly.  Only 1 priority media may run simultaneous per player.

__NB CAP Emergency Alerts automatically override all schedules, currently playing media and internal priority broadcasts. 

The date/time of  timeslots available for scheduling by any user are based on group permissions associated with that users profile. Permissions are managed by the `Administrator` group, although this may be delegated to another group. Only one Player may be scheduled at a time, although a Player may act as `Parent` to one or more players for scheduling. See [Advanced Scheduling](#adv_admin).

![Schedule Time slot](/server/img/schedule_timeslot.jpg ){: .screenshot} 

Open the schedule grid using the `schedules->schedule shows` menu option:

1. Select the Player you have permission to schedule for. 
1. Move the schedule grid to the desired week, using `<Prev` and `Next>` navigation aids.
1. Display the Playlist Tab in the sidebar.
1. Drag a Playlist from the sidebar onto the schedule.
1. Select from the available timeslots. Start time/date and duration are fixed to the time slot.
1. __Save__ .

If the new show conflicts with any scheduled show, an error will be displayed and the new show will not be saved. Review the schedule. Look for an open time slot, advancing to the following week(s) if necessary. 

Shows must be scheduled with adequate lead-time in order to be synchronized with a Player for broadcast. Allow at least __30 minutes lead time__ to ensure scheduled media can be uploaded to the Player before show time. Sufficient lead time is required to account for  `Show Lockout Time` on the destination Player.

<br/>

### Scheduling Permissions

User with advance permissions can drag Media, Playlist or a Program and override basic users time-slots.    If a slot is already scheduled, advanced user can remove or edit spots they are trying to program.

Advance users can have access to many options including, start and stop of time and date, duration.
Scheduling Mode is available with options for daily, weekly, monthly as well as every x, day, week month

### User Time Slots

![User Time Slots](/server/img/User_Time_Slots.jpg ){: .screenshot} 

Create Time Slots and Assign Users

1. Double click on screen and a menu will pop up

1. First step is to select the user to assign from the drop down menu.  This will display a list of all registered and active users.

1. Select the event mode, Single, Daily, Weekly, Monthly or every x, day, week or month

1. Select the start day (default is current date) and the  time when this user can program content

1. Set duration.   In this example the time-slot is one hour and it can be any duration.  Time Slot cannot conflict with an existing time-slot.

In the event that a user is assigned a time-slot that is not utilized or does not contain any media or Play list containing media the DPL (Default Play List) assigned to that Player will automatically start to avoid dead air

<br/>

### Priority Broadcasts

![Priority Broadcasts](/server/img/Priority_Broadcasts.png ){: .screenshot} 

The Priority broadcast will start with minimal delay as specified in OBPlayer Dashboard how often to sync for priority messages.  Selecting the default time of 00:00:00 for 3PM will start the broadcast immediately. In order to access this feature the user must have the required permissions and time must be authorized for the media to arrive at the Player in order to play.

1. Begin by selecting the Player where the emergency broadcast is to be scheduled.   Drag and drop a single media from the media window.  A menu will pop up asking for information.

1. Give the Priority broadcast a name. 

1. Enter in the frequency of the broadcast in seconds 
eg 600 sec = 5 minutes.

1. Enter in start/stop times and date range with pop up calendar

1. Click Save

<br/>

<a name="adv_admin"></a>

### Scheduling Line In

When enabled in `Player Manager` a `Line-In` button appears in top right of scheduler.  Drag `Schedule Line-In` onto schedule.  What ever is plugged into the Line-in of local machine where the player is will be passed through.  Example is a satellite audio receiver.

## User Management

### New User Sign up

![User Registration Form](/server/img/registration.jpg ){: .screenshot} 

Users may create an account from a link on the Welcome page. This may also be disable if you do not want public to sign up for new accounts in `User Admin`.

Only one registration is allowed at any given email address. Users are notified of a new, random password by email, upon registration or when a password reminder is requested. Once a user has registered, a notice is sent to the __Administrator__ . Until a user is assigned to a group, they are limited to read only browse/preview of the media library. 

The `admin` user has access to all media, playlists and schedules. As new users are added, they are assigned to a group with the appropriate set of permissions. For example, a `guest` user may browse, but not add/edit/delete items in the media library.

<br/>

### User List

![User List](/server/img/user_manager.jpg ){: .screenshot} 

The `user management` menu provides a list of user accounts, indicating group membership and most recent access.  Sort by name, last login and creation date.

<br/>

![Modify User](/server/img/user_account.jpg ){: .screenshot}

Add or Edit User account settings.

1. Change Display\User Name

1. Specify email address for reset and notifications

1. Assign user to permissions group(s)

1. Enable\Disable User Status

<br/>

### Permissions

![User Management](/server/img/user_management.png ){: .screenshot}

The `permission` menu accesses a grid for fine-grained control over access to media, Playlist and scheduling functions, on a Player by Player basis. Users in the __Administrator__ group may assign or revoke permissions to other user groups.  Additional groups may be created for granting users' permission to upload and/or download media, schedule PlayLists, or view playlogs. For example, approval for media and allocation of time slots could be delegated to a group that grants permissions on only one Player device, and has no other administrative privileges. [Advanced Admin](#adv_admin) functions establish hooks for remote Player connections.

<br/>

### Create Group

![New Permissions Groups](/server/img/New_permissions_groups.png ){: .screenshot} 

1. Name of new group

1. Select Tasks and Resources this group should have access.

1. Save

<br/>

## Player Management

Players can be a physical playout device or virtual process located on the same hardware as the player or located in another location.

### New Player

1. Begin by giving the Player a unique name,  This name will be displayed when, scheduling, emergency broadcasts and generating reports.  Give a description of the Player. 
1. Enter in the stream URL
1. Enter a password that matched with the one that was entered when setting up the Player.  See below
1. Using the radio buttons select the types of media that the Player will be supporting
1. Set time zone where the Player is located.  This is also required for the creating of Play logs which are all done in GMT
1. From the Play List window, drag and drop the DPL (Default Play List) that will be associated with this Player.  If the Player cannot locate a schedule of media to be played, it will play the DPL in a loop to avoid dead air.
1. Drag and drop station ID that you wish to associate with this Player.
1. Save  Player

__NB Leave IP address field empty__

The Player ID is automatically generated and the assigned password will be needed when configuring the Remote Player. See Remote Player Settings

### Connection Messages

![Player Settings](/server/img/Player_Settings.png ){: .screenshot} 

__Connection Messages__ for Players connect back to the server at predetermined times as set in the Player dashboard.  The “last Connect” info is continuously being updated and a graphic icon of a green check mark indicates that all connections are current and the device is in operation and communicating properly. Displays to right of newly created player showing times when last connected.  Top line shows status of Priority, Media and Playlog sync last connections time to server. __"!"__ icon if there is no heartbeat.

Displays the Version of connected player, GPS Coordinates and Last known external IP

__Pro Tip__ Connection info is also displayed with Station Icon status using Mapping Module

<br/>

### Cloning Playout Devices as Parent and Child

![Cloned Players](/server/img/cloned-players.png){: .screenshot}

Share a common schedule among a network of players.

Customize the roles of each individual player, fine tuning Parent characteristics and controls.

-`Schedules`
-`Dynamic Selections`
-`Station IDs`
-`Default Play Lists`
-`Priority Broadcasts`

<br/>

<a name="Logging"></a>

## Logging and Monitoring 

![Player Monitoring](/server/img/Player_Monitoring.png ){: .screenshot} 

Play logs are generated from the Player and sent back to OBServer via TCP/IP according to the frequency specified in the Player Dashboard settings. Reports may be generated using filters for a combination of parameters including; Player, Time and Date range, Artist, Title and Media ID.

<br/>

<a name="Modules"></a>

## Modules

![Installed Modules](/server/img/installed-modules.png){: .screenshot}

We include a couple of sample modules. Additional features may be introduced into the OB environment by third-party developers.

Check for updated list of modules

<br/>

### Legacy Modules

#### Podcast Assembler 

Drag Playlist to turn into a dynamic single file podcast with embedded track list for copyright compliance.

![Podcast Assembler](/server/img/PODcast Assembler.png){: .screenshot}

<br/>

#### RDS Metadata

Integrate metadata of stream into a RDS encoder at transmitter site.

![RDS Controller](/server/img/RDS Controller.jpg){: .screenshot}


## Updates and New Features 

![Updates](/server/img/OBServer_Install_Check.png ){: .screenshot} 

Setup observer, login to GUI as admin user, open a new browser tab
~~~~
https://IP_OF_SERVER/updates/
~~~~

## Client Settings

Login Message of the Day and a default Welcome page displayed on the initial log in screen using an inline editor.

### Email Notification Alerts

When a playlog, schedule or media sync hasn't been received  from a remote Player in 60 minutes, an advisory email will be sent to the Admin user address from the server indicating there is a problem that needs attention.


