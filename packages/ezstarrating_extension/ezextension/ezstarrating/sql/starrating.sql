CREATE TABLE IF NOT EXISTS `starrating` (
  `id` int(11) NOT NULL auto_increment,
  `created_at` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_key` varchar(32) NOT NULL,
  `rating` int(11) NOT NULL,
  `contentobject_id` int(11) NOT NULL,
  `contentobject_attribute_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`,`session_key`,`contentobject_id`,`contentobject_attribute_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
