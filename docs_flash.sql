--
-- Table structure for table `docs_flash`
--

CREATE TABLE IF NOT EXISTS `docs_flash` (
  `flash_id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `unique_name` varchar(255) NOT NULL,
  `flash` varchar(255) NOT NULL,
  `thumb` varchar(255) NOT NULL,
  `version` varchar(24) NOT NULL,
  `article_id` mediumint(8) DEFAULT NULL,
  `user_id` mediumint(8) NOT NULL,
  PRIMARY KEY (`flash_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `docs_flash`
--

INSERT INTO `docs_flash` (`flash_id`, `title`, `unique_name`, `flash`, `thumb`, `version`, `article_id`, `user_id`) VALUES
(1, 'Add a moderator', '3.0_add_moderator', '3.0_add_moderator.swf', '3.0_add_moderator.gif', '3.0', NULL, 2),
(2, 'Manage attachments', '3.0_attachments', '3.0_attachments.swf', '3.0_attachments.gif', '3.0', NULL, 2),
(3, 'Backup and restore', '3.0_backup', '3.0_backup.swf', '3.0_backup.gif', '3.0', NULL, 2),
(6, 'Add custom BBCodes', '3.0_bbcode', '3.0_bbcode.swf', '3.0_bbcode.gif', '3.0', NULL, 2),
(7, 'Custom usergroups', '3.0_custom_groups', '3.0_custom_groups.swf', '3.0_custom_groups.gif', '3.0', NULL, 2),
(8, 'Custom profile fields', '3.0_fields', '3.0_fields.swf', '3.0_custom_groups.gif', '3.0', NULL, 2),
(9, 'Managing forums', '3.0_forums', '3.0_forums.swf', '3.0_forums.gif', '3.0', NULL, 2),
(10, 'Report reasons', '3.0_report_reasons', '3.0_report_reasons.swf', '3.0_report_reasons.gif', '3.0', NULL, 2),
(11, 'Word censor', '3.0_word_censor', '3.0_word_censor.swf', '3.0_word_censor.gif', '3.0', NULL, 2),
(12, 'User security', '3.0_user_security', '3.0_user_security.swf', '3.0_user_security.gif', '3.0', NULL, 2),
(13, 'Managing ranks', '3.0_ranks', '3.0_ranks.swf', '3.0_ranks.gif', '3.0', NULL, 2),
(14, 'Mass email', '3.0_mass_email', '3.0_mass_email.swf', '3.0_mass_email.gif', '3.0', NULL, 2);
