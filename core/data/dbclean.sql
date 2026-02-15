-- phpMyAdmin SQL Dump
-- version 4.5.3.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 02, 2016 at 04:59 PM
-- Server version: 10.0.23-MariaDB-0ubuntu0.15.10.1
-- PHP Version: 5.6.11-1ubuntu3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ob3`
--

-- --------------------------------------------------------

--
-- Table structure for table `client_storage`
--

CREATE TABLE `client_storage` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `client_storage`
--

INSERT INTO `client_storage` (`id`, `user_id`, `client_name`, `data`) VALUES
(1, 0, 'obapp_web_client', '{"welcome_message":"Welcome to OpenBroadcaster."}');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 NOT NULL,
  `description` varchar(128) CHARACTER SET utf8 NOT NULL,
  `parent_device_id` int(10) UNSIGNED DEFAULT NULL,
  `use_parent_schedule` tinyint(1) NOT NULL DEFAULT '0',
  `use_parent_ids` tinyint(1) NOT NULL DEFAULT '0',
  `use_parent_dynamic` tinyint(1) NOT NULL DEFAULT '0',
  `use_parent_playlist` tinyint(1) NOT NULL DEFAULT '0',
  `use_parent_emergency` tinyint(1) NOT NULL DEFAULT '0',
  `stream_url` varchar(255) NOT NULL,
  `support_audio` tinyint(1) NOT NULL,
  `support_video` tinyint(1) NOT NULL,
  `support_images` tinyint(1) NOT NULL,
  `support_linein` tinyint(1) NOT NULL DEFAULT '0',
  `timezone` varchar(64) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `ip_address` varchar(32) DEFAULT NULL,
  `owner_id` int(11) NOT NULL DEFAULT '0',
  `last_connect` int(10) UNSIGNED DEFAULT NULL,
  `last_connect_schedule` int(10) UNSIGNED DEFAULT NULL,
  `last_connect_emergency` int(10) UNSIGNED DEFAULT NULL,
  `last_connect_playlog` int(10) UNSIGNED DEFAULT NULL,
  `last_connect_media` int(10) UNSIGNED DEFAULT NULL,
  `version` varchar(255) NOT NULL DEFAULT '',
  `default_playlist_id` int(10) UNSIGNED DEFAULT NULL,
  `station_id_image_duration` mediumint(8) UNSIGNED NOT NULL DEFAULT '15',
  `current_playlist_id` int(10) UNSIGNED DEFAULT NULL,
  `current_playlist_end` int(10) UNSIGNED DEFAULT NULL,
  `current_media_id` int(10) UNSIGNED DEFAULT NULL,
  `current_media_end` int(10) UNSIGNED DEFAULT NULL,
  `current_show_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='master table of places devices';

-- --------------------------------------------------------

--
-- Table structure for table `devices_station_ids`
--

CREATE TABLE `devices_station_ids` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `emergencies`
--

CREATE TABLE `emergencies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED DEFAULT NULL,
  `stop` int(10) UNSIGNED DEFAULT NULL,
  `duration` decimal(10,3) UNSIGNED DEFAULT NULL,
  `frequency` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `device_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `artist` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `album` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `type` enum('audio','image','video') NOT NULL DEFAULT 'audio',
  `category_id` int(11) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `genre_id` int(11) DEFAULT NULL,
  `comments` text NOT NULL,
  `filename` text NOT NULL DEFAULT '',
  `file_hash` varchar(255) NOT NULL DEFAULT '',
  `file_location` varchar(2) NOT NULL DEFAULT '',
  `format` varchar(12) NOT NULL,
  `is_copyright_owner` tinyint(4) NOT NULL DEFAULT '1',
  `duration` decimal(10,3) DEFAULT NULL,
  `owner_id` int(11) NOT NULL DEFAULT '0',
  `created` int(11) UNSIGNED NOT NULL,
  `updated` int(10) UNSIGNED NOT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('private','public') NOT NULL,
  `dynamic_select` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Dynamic table of tracks uploaded';

-- --------------------------------------------------------

--
-- Table structure for table `media_categories`
--

CREATE TABLE `media_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Master table ot track types' PACK_KEYS=0;

--
-- Dumping data for table `media_categories`
--

INSERT INTO `media_categories` (`id`, `name`) VALUES
(1, 'Music'),
(2, 'Ad or PSA'),
(3, 'Station ID'),
(4, 'Shows - Segments'),
(5, 'Emergency Broadcast'),
(6, 'News or Weather'),
(7, 'Lecture, Talk or Discussion'),
(8, 'Show Promotion'),
(9, 'Entertainment (non-music)'),
(10, 'Other'),
(11, 'Shows - Complete');

-- --------------------------------------------------------

--
-- Table structure for table `media_countries`
--

CREATE TABLE `media_countries` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='master table of countries';

--
-- Dumping data for table `media_countries`
--

INSERT INTO `media_countries` (`id`, `name`) VALUES
(1, 'Afghanistan'),
(3, 'Albania'),
(4, 'Algeria'),
(5, 'American Samoa'),
(6, 'Andorra'),
(7, 'Angola'),
(8, 'Anguilla'),
(9, 'Antarctica'),
(10, 'Antigua and Barbuda'),
(11, 'Argentina'),
(12, 'Armenia'),
(13, 'Aruba'),
(14, 'Australia'),
(15, 'Austria'),
(16, 'Azerbaijan'),
(17, 'Bahamas'),
(18, 'Bahrain'),
(19, 'Bangladesh'),
(20, 'Barbados'),
(21, 'Belarus'),
(22, 'Belgium'),
(23, 'Belize'),
(24, 'Benin'),
(25, 'Bermuda'),
(26, 'Bhutan'),
(27, 'Bolivia'),
(28, 'Bosnia and Herzegovina'),
(29, 'Botswana'),
(30, 'Bouvet Island'),
(31, 'Brazil'),
(33, 'Brunei Darussalam'),
(34, 'Bulgaria'),
(35, 'Burkina Faso'),
(36, 'Burundi'),
(37, 'Cambodia'),
(38, 'Cameroon'),
(39, 'Canada'),
(40, 'Cape Verde'),
(41, 'Cayman Islands'),
(42, 'Central African Republic'),
(43, 'Chad'),
(44, 'Chile'),
(45, 'China'),
(46, 'Christmas Island'),
(47, 'Cocos (Keeling) Islands'),
(48, 'Colombia'),
(49, 'Comoros'),
(50, 'Congo'),
(52, 'Cook Islands'),
(53, 'Costa Rica'),
(54, 'Cote D\'ivoire'),
(55, 'Croatia'),
(56, 'Cuba'),
(57, 'Cyprus'),
(58, 'Czech Republic'),
(59, 'Denmark'),
(60, 'Djibouti'),
(61, 'Dominica'),
(62, 'Dominican Republic'),
(63, 'Ecuador'),
(64, 'Egypt'),
(65, 'El Salvador'),
(66, 'Equatorial Guinea'),
(67, 'Eritrea'),
(68, 'Estonia'),
(69, 'Ethiopia'),
(70, 'Falkland Islands '),
(71, 'Faroe Islands'),
(72, 'Fiji'),
(73, 'Finland'),
(74, 'France'),
(75, 'French Guiana'),
(76, 'French Polynesia'),
(78, 'Gabon'),
(79, 'Gambia'),
(80, 'Georgia'),
(81, 'Germany'),
(82, 'Ghana'),
(83, 'Gibraltar'),
(84, 'Greece'),
(85, 'Greenland'),
(86, 'Grenada'),
(87, 'Guadeloupe'),
(88, 'Guam'),
(89, 'Guatemala'),
(90, 'Guernsey'),
(91, 'Guinea'),
(92, 'Guinea-bissau'),
(93, 'Guyana'),
(94, 'Haiti'),
(95, 'Heard and Mcdonald Islands'),
(96, 'Holy See (Vatican City State)'),
(97, 'Honduras'),
(98, 'Hong Kong'),
(99, 'Hungary'),
(100, 'Iceland'),
(101, 'India'),
(102, 'Indonesia'),
(103, 'Iran, Islamic Republic of'),
(104, 'Iraq'),
(105, 'Ireland'),
(106, 'Isle of Man'),
(107, 'Israel'),
(108, 'Italy'),
(109, 'Jamaica'),
(110, 'Japan'),
(111, 'Jersey'),
(112, 'Jordan'),
(113, 'Kazakhstan'),
(114, 'Kenya'),
(115, 'Kiribati'),
(116, 'Korea, North'),
(117, 'Korea, Republic of'),
(118, 'Kuwait'),
(119, 'Kyrgyzstan'),
(120, 'Laos'),
(121, 'Latvia'),
(122, 'Lebanon'),
(123, 'Lesotho'),
(124, 'Liberia'),
(125, 'Libyan Arab Jamahiriya'),
(126, 'Liechtenstein'),
(127, 'Lithuania'),
(128, 'Luxembourg'),
(129, 'Macao'),
(130, 'Macedonia'),
(131, 'Madagascar'),
(132, 'Malawi'),
(133, 'Malaysia'),
(134, 'Maldives'),
(135, 'Mali'),
(136, 'Malta'),
(137, 'Marshall Islands'),
(138, 'Martinique'),
(139, 'Mauritania'),
(140, 'Mauritius'),
(141, 'Mayotte'),
(142, 'Mexico'),
(143, 'Micronesia'),
(144, 'Moldova'),
(145, 'Monaco'),
(146, 'Mongolia'),
(147, 'Montenegro'),
(148, 'Montserrat'),
(149, 'Morocco'),
(150, 'Mozambique'),
(151, 'Myanmar'),
(152, 'Namibia'),
(153, 'Nauru'),
(154, 'Nepal'),
(155, 'Netherlands'),
(156, 'Netherlands Antilles'),
(157, 'New Caledonia'),
(158, 'New Zealand'),
(159, 'Nicaragua'),
(160, 'Niger'),
(161, 'Nigeria'),
(162, 'Niue'),
(163, 'Norfolk Island'),
(164, 'Northern Mariana Islands'),
(165, 'Norway'),
(166, 'Oman'),
(167, 'Pakistan'),
(168, 'Palau'),
(169, 'Palestinian Territories'),
(170, 'Panama'),
(171, 'Papua New Guinea'),
(172, 'Paraguay'),
(173, 'Peru'),
(174, 'Philippines'),
(175, 'Pitcairn'),
(176, 'Poland'),
(177, 'Portugal'),
(178, 'Puerto Rico'),
(179, 'Qatar'),
(180, 'Reunion'),
(181, 'Romania'),
(182, 'Russian Federation'),
(183, 'Rwanda'),
(184, 'Saint Helena'),
(185, 'Saint Kitts and Nevis'),
(186, 'Saint Lucia'),
(187, 'Saint Pierre and Miquelon'),
(188, 'Saint Vincent '),
(189, 'Samoa'),
(190, 'San Marino'),
(191, 'Sao Tome and Principe'),
(192, 'Saudi Arabia'),
(193, 'Senegal'),
(194, 'Serbia'),
(195, 'Seychelles'),
(196, 'Sierra Leone'),
(197, 'Singapore'),
(198, 'Slovakia'),
(199, 'Slovenia'),
(200, 'Solomon Islands'),
(201, 'Somalia'),
(202, 'South Africa'),
(204, 'Spain'),
(205, 'Sri Lanka'),
(206, 'Sudan'),
(207, 'Suriname'),
(208, 'Svalbard and Jan Mayen'),
(209, 'Swaziland'),
(210, 'Sweden'),
(211, 'Switzerland'),
(212, 'Syrian Arab Republic'),
(213, 'Taiwan, province of China'),
(214, 'Tajikistan'),
(215, 'Tanzania'),
(216, 'Thailand'),
(217, 'Timor-leste'),
(218, 'Togo'),
(219, 'Tokelau'),
(220, 'Tonga'),
(221, 'Trinidad and Tobago'),
(222, 'Tunisia'),
(223, 'Turkey'),
(224, 'Turkmenistan'),
(225, 'Turks and Caicos '),
(226, 'Tuvalu'),
(227, 'Uganda'),
(228, 'Ukraine'),
(229, 'United Arab Emirates'),
(230, 'United Kingdom'),
(231, 'United States'),
(233, 'Uruguay'),
(234, 'Uzbekistan'),
(235, 'Vanuatu'),
(236, 'Venezuela'),
(237, 'Vietnam'),
(238, 'Virgin Islands, British'),
(239, 'Virgin Islands, U.S.'),
(240, 'Wallis and Futuna'),
(241, 'Western Sahara'),
(242, 'Yemen'),
(243, 'Zambia'),
(244, 'Zimbabwe');

-- --------------------------------------------------------

--
-- Table structure for table `media_genres`
--

CREATE TABLE `media_genres` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL,
  `media_category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='master table of musical type geners';

--
-- Dumping data for table `media_genres`
--

INSERT INTO `media_genres` (`id`, `name`, `description`, `media_category_id`) VALUES
(2, 'Yukon', 'Local Yukon musicians', 1),
(7, 'Classic Rock', 'Classic Rock', 1),
(8, 'Country', 'Country', 1),
(10, 'Disco', 'Disco', 1),
(11, 'Funk', 'Funk', 1),
(12, 'Grunge', 'Grunge', 1),
(13, 'Hip Hop', 'Hip Hop', 1),
(14, 'Jazz', 'Jazz', 1),
(15, 'Metal', 'Metalhead rock and roll!', 1),
(16, 'New Age', 'New Age', 1),
(17, 'Oldies', 'Oldies', 1),
(19, 'Pop', 'Popp', 1),
(20, 'RandB', 'RandB', 1),
(21, 'Rap', 'Rap', 1),
(22, 'Reggae', 'Reggae', 1),
(23, 'Rock', 'Rock', 1),
(24, 'Techno', 'Techno', 1),
(32, 'Trip Hop', 'Trip Hop', 1),
(45, 'Alternative Rock', 'Alternative Rock', 1),
(56, 'Electronic', 'Electronic', 1),
(57, 'Pop Folk', 'Pop Folk', 1),
(62, 'ComedyFunny', 'ComedyFunny', 9),
(70, 'Native American', 'Native American', 1),
(85, 'Hard Rock', 'Hard Rock', 1),
(98, 'Progressive Rock', 'Progressive Rock', 1),
(113, 'Primus', 'Primus', 1),
(128, 'Asian-Pop', 'Asian Pop', 1),
(130, 'Eesti Pop', 'Eesti Pop', 1),
(131, 'World Beat', 'Music from around the World', 1),
(136, 'Canadien Francais', 'Canadien Francais', 1),
(137, 'Soft Rock', 'Soft Rock and Easy Listening', 1),
(138, 'Ambient', 'Ambient and Soft Electronic', 1),
(139, 'Celtic', 'Celtic and Irish', 1),
(145, 'Misc', 'Where everything else gos', 1),
(146, 'SFX', 'SFX', 1),
(147, 'Punk', 'Punk', 1),
(148, 'SoundTrack', 'SoundTrack', 1),
(151, 'Buddha Bar', 'Buddha-Bar', 1),
(157, 'Peru Folk', 'Flutes and stuff', 1),
(158, 'Yellowknife', 'Yellowknife Artists', 1),
(165, 'Landscape', 'images of landscapes', 10),
(176, 'Yellowknife', 'Yellowknife Artists', 10),
(177, 'Abstract', 'Abstract', 10),
(179, 'Adults', 'Adults', 10),
(180, 'Agriculture', 'Agriculture', 10),
(181, 'Americana', 'Americana', 10),
(183, 'Architecture', 'Architecture', 10),
(184, 'Arts', 'Arts', 10),
(185, 'AstroPhotography', 'AstroPhotography', 10),
(186, 'Baby', 'Baby', 10),
(187, 'Backgrounds', 'Backgrounds', 10),
(188, 'Boy', 'Boy', 10),
(189, 'Business', 'Business', 10),
(190, 'Celebrations', 'Celebrations', 10),
(191, 'Children', 'Children', 10),
(192, 'City', 'City', 10),
(193, 'Communications ', 'Communications ', 10),
(194, 'Computers ', 'Computers ', 10),
(195, 'Conceptual', 'Conceptual', 10),
(196, 'Couple', 'Couple', 10),
(197, 'Culture', 'Culture', 10),
(198, 'Documentary', 'Documentary', 10),
(199, 'Domestic', 'Domestic', 10),
(200, 'Earth Photos', 'EarthPhotos', 10),
(201, 'Education', 'Education', 10),
(202, 'Election', 'Election', 10),
(203, 'Entertainment', 'Entertainment', 10),
(204, 'Environmental', 'Environmental', 10),
(205, 'Families', 'Families', 10),
(206, 'Fantasy', 'Fantasy', 10),
(207, 'Financial ', 'Financial ', 10),
(208, 'Fineart', 'Fineart', 10),
(209, 'Flowers', 'Flowers', 10),
(210, 'Food', 'Food', 10),
(211, 'General', 'General', 10),
(212, 'Girl', 'Girl', 10),
(213, 'Glamour', 'Glamour', 10),
(214, 'Government', 'Government', 10),
(215, 'Health', 'Health', 10),
(216, 'Historic', 'Historic', 10),
(217, 'Holidays', 'Holidays', 10),
(218, 'Homes', 'Homes', 10),
(219, 'Humorous', 'Humorous', 10),
(220, 'Industrial', 'Industrial', 10),
(221, 'International', 'International', 10),
(222, 'Landscapes', 'Landscapes', 10),
(223, 'Leisure', 'Leisure', 10),
(224, 'Lifestyles', 'Lifestyles', 10),
(225, 'LoveRomance', 'LoveRomance', 10),
(226, 'Manufacturing', 'Manufacturing', 10),
(227, 'Medical', 'Medical', 10),
(228, 'Meetings ', 'Meetings ', 10),
(229, 'Men ', 'Men ', 10),
(230, 'Military', 'Military', 10),
(231, 'Models', 'Models', 10),
(232, 'Money ', 'Money ', 10),
(233, 'Music', 'Music', 10),
(234, 'Nature', 'Nature', 10),
(235, 'Nautical', 'Nautical', 10),
(236, 'Newspaper', 'Newspaper', 10),
(237, 'Northern', 'Northern', 10),
(238, 'Nostalgia', 'Nostalgia', 10),
(239, 'Office ', 'Office ', 10),
(240, 'Outdoors', 'Outdoors', 10),
(241, 'Patriotic', 'Patriotic', 10),
(242, 'Patterns', 'Patterns', 10),
(243, 'People ', 'People ', 10),
(244, 'Personality', 'Personality', 10),
(245, 'Pets', 'Pets', 10),
(246, 'PhotoEssay', 'PhotoEssay', 10),
(247, 'Political', 'Political', 10),
(248, 'Portrait', 'Portrait', 10),
(249, 'Recreation', 'Recreation', 10),
(250, 'Religion', 'Religion', 10),
(251, 'Science', 'Science', 10),
(252, 'Shopping', 'Shopping', 10),
(253, 'Signs', 'Signs', 10),
(254, 'SpacePhotos', 'SpacePhotos', 10),
(255, 'Sports', 'Sports', 10),
(256, 'StillLife', 'StillLife', 10),
(257, 'Symbols', 'Symbols', 10),
(258, 'Teamwork ', 'Teamwork ', 10),
(259, 'Technology ', 'Technology ', 10),
(260, 'Textures', 'Textures', 10),
(261, 'Tourism', 'Tourism', 10),
(262, 'Traditional', 'Traditional', 10),
(263, 'Transportation', 'Transportation', 10),
(264, 'Travel ', 'Travel ', 10),
(265, 'Underwater', 'Underwater', 10),
(266, 'Vintage', 'Vintage', 10),
(267, 'Weather', 'Weather', 10),
(268, 'Wildlife', 'Wildlife', 10),
(269, 'Women', 'Women', 10),
(270, 'Work and Workplace', 'Work/Workplace', 10),
(271, 'XXX', 'XXX', 10),
(272, 'Action', 'Action', 9),
(273, 'Adventure', 'Adventure', 9),
(274, 'Amateur', 'Amateur', 9),
(275, 'Animation', 'Animation', 9),
(276, 'Biography', 'Biography', 9),
(277, 'Blaxploitation', 'Blaxploitation', 9),
(278, 'Chick Flicks', 'Chick Flicks', 9),
(279, 'Christmas', 'Christmas', 9),
(280, 'Classic Hollywood', 'Classic Hollywood', 9),
(281, 'Comedy', 'Comedy', 9),
(282, 'Comic Book Based', 'Comic Book-Based', 9),
(283, 'Crime', 'Crime', 9),
(284, 'Cult Movies', 'Cult Movies', 9),
(285, 'Detective Mystery Films', 'Detective/Mystery Films', 9),
(286, 'Disaster Films', 'Disaster Films', 9),
(287, 'Documentary', 'Documentary', 9),
(288, 'Drama', 'Drama', 9),
(289, 'Experimental', 'Experimental', 9),
(290, 'Family', 'Family', 9),
(291, 'Fantasy', 'Fantasy', 9),
(292, 'Film Noir', 'Film Noir', 9),
(293, 'Game Show', 'Game-Show', 9),
(294, 'History', 'History', 9),
(295, 'Horror', 'Horror', 9),
(296, 'Martial Arts', 'Martial Arts', 9),
(297, 'Miscellaneous', 'Miscellaneous', 9),
(298, 'Mockumentary', 'Mockumentary', 9),
(299, 'Music Concerts', 'Music Concerts', 9),
(300, 'Musical', 'Musical', 9),
(301, 'News', 'News', 9),
(302, 'Northern', 'Northern', 9),
(303, 'Organized Crime', 'Organized Crime', 9),
(304, 'Reality TV', 'Reality-TV', 9),
(305, 'Road Films', 'Road Films', 9),
(306, 'Romance', 'Romance', 9),
(307, 'Satire', 'Satire', 9),
(308, 'SciFi', 'Sci-Fi', 9),
(309, 'Short', 'Short', 9),
(310, 'Silent Movies', 'Silent Movies', 9),
(311, 'Sport', 'Sport', 9),
(312, 'Supernatural Films', 'Supernatural Films', 9),
(313, 'Talk Show', 'Talk-Show', 9),
(314, 'Teen Movies', 'Teen Movies', 9),
(315, 'Thrillers Suspense', 'Thrillers/Suspense', 9),
(316, 'TrailersPreviews', 'TrailersPreviews', 9),
(317, 'War', 'War', 9),
(318, 'Western', 'Western', 9),
(319, 'XXX', 'XXX', 9),
(320, 'Blues', 'Blues Music', 1),
(321, 'Folk', 'Folk Music', 1),
(322, 'OTR Vintage Commercials', 'OTR Vintage Commercials', 9),
(323, 'Holiday', 'Holiday', 1),
(324, 'Movie Bits', 'Movie Bits', 9),
(325, 'Christmas', 'Christmas', 1),
(326, 'OTR Challenge', 'OTR Challenge', 9),
(329, 'OTR TV Themes', 'OTR TV Themes', 1),
(334, 'Alternative', 'Alternative', 1),
(335, 'Alternative Country', 'Alternative Country', 1),
(338, 'Mash-up', 'Mash-up', 1),
(339, 'Concert', 'Concert', 1),
(342, 'New Wave', 'New Wave', 1),
(343, 'Animal', 'Animal', 10),
(345, 'OTR Goon Show', 'OTR Goon Show', 9),
(348, 'Classical', 'Classical', 1),
(349, 'Minimalism', 'Minimalism', 1),
(351, 'Christmas Alt', 'Christmas Alt', 1),
(352, 'Dubstep', 'Dubstep', 1),
(353, 'Electro-Bassline-Filter', 'Electro-Bassline-Filter', 1),
(354, 'Dubtech', 'Dubtech', 1),
(355, 'House Music', 'House Music', 1),
(356, 'Chillwave-Lounge', 'Chillwave-Lounge', 1),
(357, 'Indie', 'Indie', 1),
(358, 'Experimental electronic', 'Experimental electronic', 1),
(359, 'Acoustic', 'Acoustic', 1),
(361, 'House Music (prog)', 'House Music (prog)', 1),
(362, 'House Music (Deep)', 'House Music (Deep)', 1),
(363, 'House Music (Tech)', 'House Music (Tech)', 1),
(364, 'House Music (minimal)', 'House Music (minimal)', 1),
(365, 'House Music (Electro)', 'House Music (Electro)', 1),
(982, 'Part of Complete Show', 'For example, part 1 of 2.', 4),
(983, 'Song/Media Intro', '', 4),
(984, 'Song/Media Outro', '', 4),
(985, 'Show Intro', '', 4),
(986, 'Show Outro', '', 4),
(987, 'Other', '', 4),
(988, 'CJUC Station ID', '', 3),
(989, 'CFET Station ID', '', 3),
(990, 'Other Station ID', '', 3),
(991, 'Weather', '', 6),
(992, 'Other', '', 7),
(993, 'Emergency Broadcast', '', 5),
(994, 'Commercial Ad', '', 2),
(995, 'Public Service Announcement', '', 2),
(996, 'Complete Shows', '', 11),
(997, 'Show Promotion', '', 8),
(998, 'News', '', 6),
(999, 'Other', 'Other', 10),
(1002, 'RA Podcasts', 'Resident Advisor Podcasts', 1),
(1003, 'Moombahton', 'Moombahton', 1),
(1004, 'Soul', 'Soul', 1);

-- --------------------------------------------------------

--
-- Table structure for table `media_languages`
--

CREATE TABLE `media_languages` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='master table of languages';

--
-- Dumping data for table `media_languages`
--

INSERT INTO `media_languages` (`id`, `name`) VALUES
(1, 'Abenaki'),
(2, 'Afrikaans'),
(3, 'Albanian'),
(4, 'Algonquian'),
(5, 'Amharic'),
(6, 'Arabic(Egyptian)'),
(7, 'Arabic(Levantine)'),
(8, 'Arabic(ModernStandard)'),
(9, 'Arabic(Moroccan)'),
(10, 'Aramaic'),
(11, 'Armenian'),
(12, 'Assamese'),
(13, 'Assan'),
(14, 'Assiniboine'),
(15, 'Atikamekw'),
(16, 'Aymara'),
(17, 'Azerbaijani'),
(18, 'Babine-Witsuwit’en'),
(19, 'Bahasa'),
(20, 'Balochi'),
(21, 'Basque'),
(22, 'Belarusian'),
(23, 'Bengali'),
(24, 'Berber'),
(25, 'Beothuk'),
(26, 'Berens-River-Ojibwe'),
(27, 'Bislama'),
(28, 'Border-Lakes-Ojibwe'),
(29, 'Bosnian'),
(30, 'Bulgarian'),
(31, 'Burmese'),
(32, 'Cantonese'),
(33, 'Carrier'),
(34, 'Catalan'),
(35, 'Cayuga'),
(36, 'Cebuano'),
(37, 'Cherokee'),
(38, 'Chilcotin'),
(39, 'Chinook'),
(40, 'Chipewyan'),
(41, 'Coast-Salish'),
(42, 'Corsican'),
(43, 'Cree'),
(44, 'Croatian'),
(45, 'Czech'),
(46, 'Dane-zaa'),
(47, 'Danish'),
(48, 'Dari'),
(49, 'Delaware'),
(50, 'Ditidaht'),
(51, 'Dogrib'),
(52, 'Dutch'),
(53, 'Egyptian'),
(54, 'English'),
(55, 'Esperanto'),
(56, 'Estonian'),
(57, 'Farsi'),
(58, 'Finnish'),
(59, 'French'),
(60, 'Gitxsan'),
(61, 'Georgian'),
(62, 'German'),
(63, 'Greek'),
(64, 'Guarani'),
(65, 'Gujarati'),
(66, 'Gwich’in'),
(67, 'Haida'),
(68, 'Haisla'),
(69, 'Halkomelem'),
(70, 'HaitianCreole'),
(71, 'Han'),
(72, 'Hausa'),
(73, 'Hawaiian'),
(74, 'HawaiianCreole'),
(75, 'Hebrew'),
(76, 'Heiltsuk'),
(77, 'Hindi'),
(78, 'Hungarian'),
(79, 'Icelandic'),
(80, 'Ilokano'),
(81, 'Indonesia'),
(82, 'Inuinnaqtun'),
(83, 'Inuktituk'),
(84, 'Irish'),
(85, 'Iroquoian'),
(86, 'Italian'),
(87, 'Japanese'),
(88, 'Javanese'),
(89, 'Kannada'),
(90, 'Kaska'),
(91, 'Kazakh'),
(92, 'Khmer'),
(93, 'Klallam'),
(94, 'Korean'),
(95, 'Kurdi'),
(96, 'Kutenai'),
(97, 'Kyrgyz'),
(98, 'Lakota'),
(99, 'Lao'),
(100, 'Lakota'),
(101, 'Latin'),
(102, 'Latvian'),
(103, 'Laurentian'),
(104, 'Lithuanian'),
(105, 'Lillooet'),
(106, 'Macedonian'),
(107, 'Malagasy'),
(108, 'Malayalam'),
(109, 'Malecite'),
(110, 'Mandarin'),
(111, 'Marathi'),
(112, 'Maya'),
(113, 'Melayu'),
(114, 'Mi’kmaq'),
(115, 'Mongolian'),
(116, 'Mohawk'),
(117, 'Moose-Cree'),
(118, 'Munsee'),
(119, 'Nahuatl'),
(120, 'Naskapi'),
(121, 'Navajo'),
(122, 'Nepali'),
(123, 'Netsilik'),
(124, 'Nicola'),
(125, 'Nipissing'),
(126, 'Norwegian'),
(127, 'Nuu-chah-nulth'),
(128, 'Nuxalk'),
(129, 'Norwegian'),
(130, 'Ojibwa'),
(131, 'Okanagan'),
(132, 'Oneida'),
(133, 'Onondaga'),
(134, 'Oowekyala'),
(135, 'Oriya'),
(136, 'Oromo'),
(137, 'Ottawa'),
(138, 'Papuan'),
(139, 'Pashto'),
(140, 'Plateau'),
(141, 'Polish'),
(142, 'Portuguese'),
(143, 'Potawatomi'),
(144, 'Punjabi'),
(145, 'Quechua'),
(146, 'Quiche'),
(147, 'Romanian'),
(148, 'Romany'),
(149, 'Russian'),
(150, 'Saanich'),
(151, 'Sanskrit'),
(152, 'Sarcee'),
(153, 'Sechelt'),
(154, 'Serbian'),
(155, 'Shuswap'),
(156, 'Sinixt'),
(157, 'Slavey'),
(158, 'Slovak'),
(159, 'Slovenian'),
(160, 'Somali'),
(161, 'Southern-Tsimshian'),
(162, 'Spanish'),
(163, 'Stoney'),
(164, 'Susquehannock'),
(165, 'Swahili'),
(166, 'Swampy-Cree'),
(167, 'Swedish'),
(168, 'Tagalog'),
(169, 'Tagish'),
(170, 'Tajik'),
(171, 'Tahltan'),
(172, 'Tamil'),
(173, 'Telugu'),
(174, 'Thai'),
(175, 'Thompson'),
(176, 'Tibetan'),
(177, 'Tlingit'),
(178, 'TokPisin'),
(179, 'Turkish'),
(180, 'Turkmen'),
(181, 'Tuscarora'),
(182, 'Tsimshian'),
(183, 'Tutchone'),
(184, 'Ukrainian'),
(185, 'Urdu'),
(186, 'Utkuhiksalik'),
(187, 'Uyghur'),
(188, 'Uzbek'),
(189, 'Vietnamese'),
(190, 'Wakashan'),
(191, 'Welsh'),
(192, 'Wyandot'),
(193, 'Xhosa'),
(194, 'Yiddish'),
(195, 'Yoruba'),
(196, 'Yucatec'),
(197, 'Zapotec'),
(198, 'Zulu');


-- --------------------------------------------------------

--
-- Table structure for table `media_searches`
--

CREATE TABLE `media_searches` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `query` text NOT NULL,
  `type` enum('saved','history') NOT NULL,
  `default` tinyint(1) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `directory` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(10) UNSIGNED NOT NULL,
  `event` varchar(255) NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `toggled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `type` enum('standard','advanced','live_assist') NOT NULL DEFAULT 'standard',
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `status` enum('private','public') NOT NULL,
  `created` int(10) UNSIGNED NOT NULL,
  `updated` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='master table of playlist';

-- --------------------------------------------------------

--
-- Table structure for table `playlists_items`
--

CREATE TABLE `playlists_items` (
  `id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL DEFAULT '0',
  `item_id` int(11) DEFAULT NULL,
  `item_type` enum('media','dynamic','station_id','breakpoint') NOT NULL DEFAULT 'media',
  `ord` decimal(10,3) DEFAULT '0.000',
  `duration` decimal(10,3) UNSIGNED DEFAULT NULL,
  `dynamic_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dynamic_num_items` int(10) UNSIGNED DEFAULT NULL,
  `dynamic_image_duration` int(10) UNSIGNED DEFAULT NULL,
  `dynamic_query` text CHARACTER SET utf8
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Dynamic table of detail playlist';

-- --------------------------------------------------------

--
-- Table structure for table `playlists_liveassist_buttons`
--

CREATE TABLE `playlists_liveassist_buttons` (
  `id` int(10) UNSIGNED NOT NULL,
  `playlist_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `button_playlist_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `playlog`
--

CREATE TABLE `playlog` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `artist` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `timestamp` decimal(12,2) NOT NULL,
  `context` enum('show','emerg','fallback') NOT NULL,
  `emerg_id` int(10) UNSIGNED NOT NULL,
  `notes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('media','playlist','linein') NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `duration` mediumint(8) UNSIGNED NOT NULL,
  `end` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_media_cache`
--

CREATE TABLE `schedules_media_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `mode` enum('once','recurring','default_playlist') NOT NULL,
  `schedule_id` int(10) UNSIGNED DEFAULT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `duration` mediumint(9) UNSIGNED NOT NULL,
  `data` mediumtext CHARACTER SET utf8 NOT NULL,
  `created` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_permissions`
--

CREATE TABLE `schedules_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `duration` mediumint(8) UNSIGNED NOT NULL,
  `end` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_permissions_recurring`
--

CREATE TABLE `schedules_permissions_recurring` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mode` enum('daily','weekly','monthly','xdays','xweeks','xmonths') NOT NULL,
  `x_data` smallint(5) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `duration` mediumint(8) UNSIGNED NOT NULL,
  `stop` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_permissions_recurring_expanded`
--

CREATE TABLE `schedules_permissions_recurring_expanded` (
  `id` int(10) UNSIGNED NOT NULL,
  `recurring_id` int(10) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `end` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_recurring`
--

CREATE TABLE `schedules_recurring` (
  `id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `item_type` enum('media','playlist','linein') NOT NULL,
  `mode` enum('daily','weekly','monthly','xdays','xweeks','xmonths') NOT NULL,
  `x_data` smallint(5) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `duration` mediumint(8) UNSIGNED NOT NULL,
  `stop` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schedules_recurring_expanded`
--

CREATE TABLE `schedules_recurring_expanded` (
  `id` int(10) UNSIGNED NOT NULL,
  `recurring_id` int(10) UNSIGNED NOT NULL,
  `start` int(10) UNSIGNED NOT NULL,
  `end` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'audio_formats', 'flac,mp3,ogg'),
(2, 'video_formats', 'avi,mpg,ogg'),
(3, 'image_formats', 'jpg,png'),
(5, 'dbver', '20160409'),
(8, 'core_metadata', '{"artist":"required","album":"required","year":"required","category_id":"required","country_id":"enabled","language_id":"enabled","comments":"enabled"}');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(32) NOT NULL,
  `expiry` int(11) UNSIGNED NOT NULL,
  `type` enum('audio','image','video') DEFAULT NULL,
  `format` varchar(12) DEFAULT NULL,
  `duration` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `key_expiry` int(10) UNSIGNED NOT NULL,
  `last_access` int(10) UNSIGNED NOT NULL,
  `created` int(10) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `display_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `key`, `key_expiry`, `last_access`, `created`, `enabled`, `display_name`) VALUES
(1, 'Admin', 'admin', 'admin@example.com', '', '', 1462226183, 1462222583, 0, 1, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`id`, `name`) VALUES
(1, 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `users_permissions`
--

CREATE TABLE `users_permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users_permissions`
--

INSERT INTO `users_permissions` (`id`, `name`, `description`, `category`) VALUES
(1, 'create_own_media', 'create, edit, and delete own media', 'media'),
(2, 'approve_own_media', 'approve own media', 'media'),
(3, 'manage_media', 'create, edit, approve, and delete all media', 'media'),
(4, 'create_own_playlists', 'create, edit, and delete own playlists', 'playlists'),
(5, 'manage_playlists', 'create, edit, and delete all playlists', 'playlists'),
(9, 'manage_media_settings', 'manage media settings', 'administration'),
(11, 'manage_schedule_permissions', 'manage schedule timeslots', 'device'),
(12, 'manage_devices', 'manage devices', 'administration'),
(13, 'manage_permissions', 'manage groups and permissions', 'administration'),
(18, 'manage_emergency_broadcasts', 'create, edit, and delete emergency broadcasts', 'device'),
(19, 'view_device_monitor', 'view device playlogs', 'device'),
(26, 'manage_users', 'create, edit, and delete users', 'administration'),
(27, 'advanced_show_scheduling', 'enable advanced show scheduling', 'user interface'),
(28, 'manage_global_client_storage', 'manage client/interface settings', 'administration'),
(29, 'download_media', 'download all media', 'media'),
(30, 'manage_modules', 'install and uninstall modules', 'administration');

-- --------------------------------------------------------

--
-- Table structure for table `users_permissions_to_groups`
--

CREATE TABLE `users_permissions_to_groups` (
  `id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_settings`
--

CREATE TABLE `users_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_to_groups`
--

CREATE TABLE `users_to_groups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users_to_groups`
--

INSERT INTO `users_to_groups` (`id`, `user_id`, `group_id`) VALUES
(1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_storage`
--
ALTER TABLE `client_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `devices_station_ids`
--
ALTER TABLE `devices_station_ids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `start_timestamp` (`start`,`stop`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist` (`artist`),
  ADD KEY `title` (`title`),
  ADD KEY `duration` (`duration`);

--
-- Indexes for table `media_categories`
--
ALTER TABLE `media_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_countries`
--
ALTER TABLE `media_countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_genres`
--
ALTER TABLE `media_genres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_languages`
--
ALTER TABLE `media_languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_searches`
--
ALTER TABLE `media_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `default` (`default`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event` (`event`,`device_id`),
  ADD KEY `toggled` (`toggled`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `playlists_items`
--
ALTER TABLE `playlists_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pl_id_index` (`playlist_id`);

--
-- Indexes for table `playlists_liveassist_buttons`
--
ALTER TABLE `playlists_liveassist_buttons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlist_id` (`playlist_id`,`order_id`);

--
-- Indexes for table `playlog`
--
ALTER TABLE `playlog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`,`emerg_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules_media_cache`
--
ALTER TABLE `schedules_media_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`,`start`),
  ADD KEY `duration` (`duration`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `start` (`start`);

--
-- Indexes for table `schedules_permissions`
--
ALTER TABLE `schedules_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules_permissions_recurring`
--
ALTER TABLE `schedules_permissions_recurring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules_permissions_recurring_expanded`
--
ALTER TABLE `schedules_permissions_recurring_expanded`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules_recurring`
--
ALTER TABLE `schedules_recurring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules_recurring_expanded`
--
ALTER TABLE `schedules_recurring_expanded`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `setting` (`name`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `username` (`username`),
  ADD KEY `email` (`email`),
  ADD KEY `active` (`enabled`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_permissions`
--
ALTER TABLE `users_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users_permissions_to_groups`
--
ALTER TABLE `users_permissions_to_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_id` (`permission_id`,`group_id`);

--
-- Indexes for table `users_settings`
--
ALTER TABLE `users_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users_to_groups`
--
ALTER TABLE `users_to_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_storage`
--
ALTER TABLE `client_storage`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `devices_station_ids`
--
ALTER TABLE `devices_station_ids`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `emergencies`
--
ALTER TABLE `emergencies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `media_categories`
--
ALTER TABLE `media_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `media_countries`
--
ALTER TABLE `media_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;
--
-- AUTO_INCREMENT for table `media_genres`
--
ALTER TABLE `media_genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;
--
-- AUTO_INCREMENT for table `media_languages`
--
ALTER TABLE `media_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;
--
-- AUTO_INCREMENT for table `media_searches`
--
ALTER TABLE `media_searches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `playlists_items`
--
ALTER TABLE `playlists_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `playlists_liveassist_buttons`
--
ALTER TABLE `playlists_liveassist_buttons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `playlog`
--
ALTER TABLE `playlog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules_media_cache`
--
ALTER TABLE `schedules_media_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;
--
-- AUTO_INCREMENT for table `schedules_permissions`
--
ALTER TABLE `schedules_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules_permissions_recurring`
--
ALTER TABLE `schedules_permissions_recurring`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules_permissions_recurring_expanded`
--
ALTER TABLE `schedules_permissions_recurring_expanded`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules_recurring`
--
ALTER TABLE `schedules_recurring`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedules_recurring_expanded`
--
ALTER TABLE `schedules_recurring_expanded`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=631;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `users_groups`
--
ALTER TABLE `users_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT for table `users_permissions`
--
ALTER TABLE `users_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT for table `users_permissions_to_groups`
--
ALTER TABLE `users_permissions_to_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users_settings`
--
ALTER TABLE `users_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users_to_groups`
--
ALTER TABLE `users_to_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
