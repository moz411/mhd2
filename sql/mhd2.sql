-- phpMyAdmin SQL Dump
-- version 2.6.0-rc2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Aug 15, 2005 at 08:12 PM
-- Server version: 4.1.10
-- PHP Version: 4.3.8
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

CREATE TABLE `config` (
  `base_url` varchar(30) default NULL,
  `title` varchar(10) default NULL,
  `version` varchar(5) default NULL,
  `locale` varchar(5) default NULL,
  `encoding` varchar(10) default NULL,
  `theme` varchar(10) default NULL,
  `contact_db` enum('ldap','sql') default NULL,
  `ldap_host` varchar(20) default NULL,
  `ldap_tree` varchar(50) default NULL,
  `ldap_user` varchar(20) default NULL,
  `ldap_password` varchar(20) default NULL,
  `mail_host` varchar(20) default NULL,
  `mail_user` varchar(20) default NULL,
  `mail_password` varchar(20) default NULL,
  `doc_dir` varchar(50) default NULL,
  `range` smallint(6) default NULL,
  `repository` varchar(50) default NULL,
  `max_file_size` smallint(6) default NULL,
  `timeout` smallint(6) default NULL,
  `webdav_host` varchar(20) default NULL,
  `webdav_user` varchar(20) default NULL,
  `webdav_password` varchar(20) default NULL
) ENGINE=MyISAM;

-- 
-- Dumping data for table `config`
-- 

INSERT INTO `config` VALUES ('http://localhost/mhd2', 'MHD²', '0.1', 'fr_FR', 'ISO-8859-1', 'default', 'sql', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30, NULL, 100, 3600, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `contacts`
-- 

CREATE TABLE `contacts` (
  `uid` tinytext,
  `cid` tinytext,
  `firstname` tinytext,
  `lastname` tinytext,
  `phone` tinytext,
  `email` tinytext,
  `fax` tinytext,
  `address` tinytext,
  `job` tinytext,
  FULLTEXT KEY `firstname` (`firstname`),
  FULLTEXT KEY `lastname` (`lastname`),
  FULLTEXT KEY `phone` (`phone`),
  FULLTEXT KEY `email` (`email`)
) ENGINE=MyISAM;

-- 
-- Dumping data for table `contacts`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `contracts`
-- 

CREATE TABLE `contracts` (
  `id` bigint(20) NOT NULL auto_increment,
  `cid` bigint(20) default NULL,
  `number` tinytext NOT NULL,
  `administrative_contact` tinytext,
  `technical_contact` tinytext,
  `IC` tinytext,
  `ITC` tinytext,
  `start_date` date default NULL,
  `duration` smallint(6) default NULL,
  `delivery_address` text,
  `facturation` tinytext,
  `payment` tinytext,
  `agency` tinytext,
  `partner_soft` tinytext,
  `partner_soft_contract` tinytext,
  `partner_hard` tinytext,
  `partner_hard_contract` tinytext,
  `comments` text,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `number` (`number`),
  FULLTEXT KEY `facturation` (`facturation`),
  FULLTEXT KEY `payment` (`payment`),
  FULLTEXT KEY `location` (`agency`),
  FULLTEXT KEY `comments` (`comments`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `contracts`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `customers`
-- 

CREATE TABLE `customers` (
  `id` int(11) NOT NULL auto_increment,
  `code` int(11) default NULL,
  `name` tinytext NOT NULL,
  `address` tinytext,
  `comments` text,
  `phone` tinytext,
  `fax` tinytext,
  `email` tinytext,
  `administrative_contact` tinytext,
  `technical_contact` tinytext,
  `type` tinytext,
  `IC` tinytext,
  `ITC` tinytext,
  `logo` tinyblob,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `address` (`address`),
  FULLTEXT KEY `comments` (`comments`)
) ENGINE=MyISAM COMMENT='Fiches clients' AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `customers`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `events`
-- 

CREATE TABLE `events` (
  `id` int(7) unsigned NOT NULL auto_increment,
  `tid` int(7) unsigned NOT NULL default '0',
  `description` text,
  `date` timestamp,
  `added_by` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ticket_key` (`tid`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `events`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `groups`
-- 

CREATE TABLE `groups` (
  `name` tinytext NOT NULL,
  `addjob` enum('Y','N') default NULL,
  `addevent` enum('Y','N') default NULL,
  `viewjobs` enum('Y','N') default NULL,
  `printjob` enum('Y','N') default NULL,
  `exportcsv` enum('Y','N') default NULL,
  `showfiles` enum('Y','N') default NULL,
  `addfile` enum('Y','N') default NULL,
  `showmails` enum('Y','N') default NULL,
  `search` enum('Y','N') default NULL,
  `addcontract` enum('Y','N') default NULL,
  `viewcontracts` enum('Y','N') default NULL,
  `additem` enum('Y','N') default NULL,
  `printcontract` enum('Y','N') default NULL,
  `addcustomer` enum('Y','N') default NULL,
  `viewcustomers` enum('Y','N') default NULL,
  `addcontact` enum('Y','N') default NULL,
  `viewcontacts` enum('Y','N') default NULL,
  `booking` enum('Y','N') default NULL,
  `viewdocs` enum('Y','N') default NULL,
  `showdoc` enum('Y','N') default NULL,
  `stats` enum('Y','N') default NULL,
  `config` enum('Y','N') default NULL,
  `users` enum('Y','N') default NULL,
  `groups` enum('Y','N') default NULL,
  `logout` enum('Y','N') default NULL,
  PRIMARY KEY  (`name`(20))
) ENGINE=MyISAM;

-- 
-- Dumping data for table `groups`
-- 

INSERT INTO `groups` VALUES ('admins', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');
INSERT INTO `groups` VALUES ('hotliners', 'Y', 'Y', 'Y', 'Y', NULL, 'Y', 'Y', 'Y', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 'Y', 'Y', NULL, 'Y', 'Y', NULL, NULL, NULL, NULL, 'Y');

-- --------------------------------------------------------

-- 
-- Table structure for table `items`
-- 

CREATE TABLE `items` (
  `id` bigint(20) NOT NULL auto_increment,
  `contractid` bigint(20) NOT NULL default '0',
  `type` tinytext,
  `pn` tinytext,
  `sn` tinytext,
  `hostid` tinytext,
  `designation` text,
  `renewal` date default NULL,
  `duration` tinyint(4) default NULL,
  `quantity` tinyint(4) NOT NULL default '1',
  `high_cost` decimal(8,2) default NULL,
  `low_cost` decimal(8,2) default NULL,
  `answer_time` tinytext,
  `answer_type` tinytext,
  `comments` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `items`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `tickets`
-- 

CREATE TABLE `tickets` (
  `id` bigint(20) NOT NULL auto_increment,
  `cid` bigint(20) default NULL,
  `item` bigint(20) default NULL,
  `category` tinytext,
  `detail` text,
  `priority` smallint(1) NOT NULL default '3',
  `opened_by` tinytext,
  `closed_by` tinytext,
  `assigned_to` tinytext,
  `summary` tinytext NOT NULL,
  `status` tinytext,
  `contact` tinytext,
  `date_updated` timestamp,
  `date_opened` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_closed` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `detail` (`detail`),
  FULLTEXT KEY `summary` (`summary`)
) ENGINE=MyISAM AUTO_INCREMENT=1000 ;

-- 
-- Dumping data for table `tickets`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `users`
-- 

CREATE TABLE `users` (
  `id` tinytext NOT NULL,
  `passwd` tinytext NOT NULL,
  `firstname` tinytext,
  `lastname` tinytext,
  `phone` tinytext,
  `email` tinytext,
  `groupe` tinytext,
  `addjob` enum('Y','N') default NULL,
  `addevent` enum('Y','N') default NULL,
  `viewjobs` enum('Y','N') default NULL,
  `printjob` enum('Y','N') default NULL,
  `exportcsv` enum('Y','N') default NULL,
  `showfiles` enum('Y','N') default NULL,
  `addfile` enum('Y','N') default NULL,
  `showmails` enum('Y','N') default NULL,
  `search` enum('Y','N') default NULL,
  `addcontract` enum('Y','N') default NULL,
  `viewcontracts` enum('Y','N') default NULL,
  `additem` enum('Y','N') default NULL,
  `printcontract` enum('Y','N') default NULL,
  `addcustomer` enum('Y','N') default NULL,
  `viewcustomers` enum('Y','N') default NULL,
  `addcontact` enum('Y','N') default NULL,
  `viewcontacts` enum('Y','N') default NULL,
  `booking` enum('Y','N') default NULL,
  `viewdocs` enum('Y','N') default NULL,
  `showdoc` enum('Y','N') default NULL,
  `stats` enum('Y','N') default NULL,
  `config` enum('Y','N') default NULL,
  `users` enum('Y','N') default NULL,
  `groups` enum('Y','N') default NULL,
  `logout` enum('Y','N') default NULL,
  PRIMARY KEY  (`id`(8))
) ENGINE=MyISAM;

CREATE TABLE `sessions` (
  `id` varchar(32) NOT NULL default '',
  `last_updated` timestamp,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `session_data` longtext,
  PRIMARY KEY  (`id`),
  KEY `last_updated` (`last_updated`)
) ENGINE=MyISAM ;
-- 
-- Dumping data for table `users`
-- 

INSERT INTO `users` VALUES ('admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', '', '', '', 'admins', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL);

/* Workaround bug mysql */
ALTER TABLE `events` CHANGE `date` `date` TIMESTAMP( 14 ) NOT NULL DEFAULT 'NOW';
ALTER TABLE `tickets` CHANGE `date_updated` `date_updated` TIMESTAMP( 14 ) NOT NULL DEFAULT 'NOW';
ALTER TABLE `sessions` CHANGE `last_updated` `last_updated` TIMESTAMP( 14 ) NOT NULL DEFAULT 'NOW';
