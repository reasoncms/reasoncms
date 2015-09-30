--
-- Database: `reason_giving_form`
--

-- --------------------------------------------------------
CREATE DATABASE `reason_giving_form`;

USE `reason_giving_form`;
-- ---------------------------
--
-- Table structure for table `gift_giver`
--

CREATE TABLE `gift_giver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submit_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `REFNUM` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `spouse_first_name` varchar(100) DEFAULT NULL,
  `spouse_last_name` varchar(100) DEFAULT NULL,
  `address_1` varchar(256) DEFAULT NULL,
  `address_2` varchar(256) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_province` varchar(3) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `country` varchar(3) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone_type` enum('Home','Business','Cell') DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `luther_affiliation` varchar(100) DEFAULT NULL,
  `class_year` int(4) DEFAULT NULL,
  `have_estate_plans` enum('Yes','No') DEFAULT NULL,
  `send_estate_info` enum('Yes','No') DEFAULT NULL,
  `match_gift` enum('Yes','No') DEFAULT NULL,
  `employer_name` varchar(256) DEFAULT NULL,
  `gift_prompt` varchar(256) DEFAULT NULL,
  `gift_prompt_details` varchar(256) DEFAULT NULL,
  `split_gift` enum('Yes','No') DEFAULT NULL,
  `designation` varchar(256) DEFAULT NULL,
  `split_designations` varchar(256) DEFAULT NULL,
  `comments` text,
  `dedication` enum('Memory','Honor') DEFAULT NULL,
  `dedication_details` varchar(256) DEFAULT NULL,
  `mail_receipt` varchar(100) DEFAULT NULL,
  `email_new_charges` varchar(100) DEFAULT NULL,
  `billing_address` enum('entered','new') DEFAULT NULL,
  `billing_street_address` varchar(256) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state_province` varchar(3) DEFAULT NULL,
  `billing_zip` varchar(10) DEFAULT NULL,
  `billing_country` varchar(3) DEFAULT NULL,
  `credit_card_type` varchar(10) DEFAULT NULL,
  `credit_card_number` varchar(20) DEFAULT NULL,
  `credit_card_expiration_month` varchar(2) DEFAULT NULL,
  `credit_card_expiration_year` varchar(4) DEFAULT NULL,
  `credit_card_name` varchar(256) DEFAULT NULL,
  `confirmation_message` text,
  `submitter_ip` varchar(37) DEFAULT NULL,
  `status` enum('LIVE','TEST') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Table structure for table `gift_pledge`
--

CREATE TABLE `gift_pledge` (
  `created` datetime NOT NULL,
  `amount` varchar(100) DEFAULT NULL,
  `payperiod` varchar(256) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `RPREF` varchar(256) DEFAULT NULL,
  `PROFILEID` varchar(100) DEFAULT NULL,
  `giver_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gift_transaction`
--

CREATE TABLE `gift_transaction` (
  `created` datetime NOT NULL,
  `amount` varchar(100) DEFAULT NULL,
  `PNREF` varchar(256) DEFAULT NULL,
  `AUTHCODE` varchar(256) DEFAULT NULL,
  `giver_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

GRANT SELECT , INSERT , UPDATE ON  `reason\_giving\_form` . * TO  'reason_user'@'www.luther.edu';
GRANT SELECT , INSERT , UPDATE ON  `reason\_giving\_form` . * TO  'reason_user'@'reason.luther.edu';
GRANT SELECT , INSERT , UPDATE ON  `reason\_giving\_form` . * TO  'reason_user'@'198.133.77.45';
