/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE TABLE IF NOT EXISTS `mmrpg_users` (
  `user_id` mediumint(8) NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `role_id` mediumint(8) NOT NULL DEFAULT '3' COMMENT 'Role ID',
  `user_name` varchar(128) NOT NULL COMMENT 'User Name',
  `user_name_clean` varchar(128) NOT NULL COMMENT 'User Name Clean',
  `user_name_public` varchar(128) NOT NULL COMMENT 'User Name Public',
  `user_password_encoded` varchar(128) NOT NULL COMMENT 'User Password Encoded',
  `user_omega` varchar(128) NOT NULL COMMENT 'User Omega',
  `user_gender` varchar(10) NOT NULL DEFAULT 'male' COMMENT 'User Gender',
  `user_profile_text` text NOT NULL COMMENT 'User Profile Text',
  `user_credit_text` text NOT NULL COMMENT 'User Credit Text',
  `user_admin_text` text NOT NULL COMMENT 'User Admin Text',
  `user_credit_line` varchar(255) NOT NULL COMMENT 'User Credit Line',
  `user_image_path` varchar(255) NOT NULL COMMENT 'User Image Path',
  `user_background_path` varchar(255) NOT NULL COMMENT 'User Background Path',
  `user_colour_token` varchar(100) NOT NULL COMMENT 'User Colour Token',
  `user_email_address` varchar(128) NOT NULL COMMENT 'User Email Address',
  `user_website_address` varchar(255) NOT NULL COMMENT 'User Website Address',
  `user_ip_addresses` text NOT NULL COMMENT 'User IP Addresses',
  `user_game_difficulty` varchar(32) NOT NULL DEFAULT 'normal' COMMENT 'User Game Difficulty',
  `user_threads_upvoted` text NOT NULL COMMENT 'User Threads Upvoted',
  `user_threads_downvoted` text NOT NULL COMMENT 'User Threads Downvoted',
  `user_posts_upvoted` text NOT NULL COMMENT 'User Posts Upvoted',
  `user_posts_downvoted` text NOT NULL COMMENT 'User Posts Downvoted',
  `user_date_created` int(8) NOT NULL DEFAULT '0' COMMENT 'User Date Created',
  `user_date_accessed` int(8) NOT NULL DEFAULT '0' COMMENT 'User Date Accessed',
  `user_date_modified` int(8) NOT NULL DEFAULT '0' COMMENT 'User Date Created',
  `user_date_birth` int(8) NOT NULL DEFAULT '0' COMMENT 'User Date Birth',
  `user_last_login` int(8) NOT NULL DEFAULT '0' COMMENT 'User Last Login',
  `user_backup_login` int(8) NOT NULL DEFAULT '0' COMMENT 'User Backup Login',
  `user_flag_approved` smallint(1) NOT NULL DEFAULT '0' COMMENT 'User Flag Approved',
  `user_flag_postpublic` smallint(1) NOT NULL DEFAULT '1' COMMENT 'User Flag Post Public',
  `user_flag_postprivate` smallint(1) NOT NULL DEFAULT '1' COMMENT 'User Flag Post Private',
  `user_flag_allowchat` smallint(1) NOT NULL DEFAULT '1' COMMENT 'User Flag Allow Chat',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name_clean` (`user_name_clean`),
  KEY `user_flag_approved` (`user_flag_approved`),
  KEY `user_flag_postpublic` (`user_flag_postpublic`),
  KEY `user_flag_postprivate` (`user_flag_postprivate`),
  KEY `user_flag_allowchat` (`user_flag_allowchat`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
