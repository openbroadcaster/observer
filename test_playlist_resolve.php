<?php
// Include the core components
//  This script should be run as php test_playlist_resolve.php 1767 1
// Allows you to test playlist resolution, and duplicate prevention
require_once 'components.php';

// Get the models instance through the framework
$load = OBFLoad::get_instance();
$playlistsModel = $load->model('Playlists');

// Get playlistId and playerId from command-line arguments
if ($argc < 3) {
    echo "Usage: php test_playlist_resolve.php <playlistId> <playerId>\n";
    exit(1);
}

$playlistId = (int)$argv[1];
$playerId = (int)$argv[2];

// Resolve the playlist
try {
    $resolvedItems = $playlistsModel('resolve', 
        $playlistId,      // Specific playlist ID to test
        $playerId,        // Player ID
        false,            // No parent player
        new DateTime(),   // Start time
        null              // Max duration
    );

    // Print out resolved items in simplified format
    echo "Resolved Playlist Items:\n";
    foreach ($resolvedItems as $item) {
        echo "{$item['context']}, {$item['id']}\n";
    }

    // Check for duplicates manually
    $mediaIds = array_column($resolvedItems, 'id');
    $duplicates = array_diff_assoc($mediaIds, array_unique($mediaIds));
    
    echo "\nDuplicates Found: ";
    if (empty($duplicates)) {
        echo "None\n";
    } else {
        echo implode(", ", $duplicates) . "\n";
    }

    // Additional analysis
    echo "\nTotal Items: " . count($resolvedItems) . "\n";
    echo "Unique Items: " . count(array_unique($mediaIds)) . "\n";

} catch (Exception $e) {
    echo "Error resolving playlist: " . $e->getMessage() . "\n";
}
