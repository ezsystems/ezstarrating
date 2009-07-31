CREATE TABLE IF NOT EXISTS `ezstarrating` (
  `id` int(11) NOT NULL auto_increment,
  `created_at` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_key` varchar(32) NOT NULL,
  `rating` int(11) NOT NULL,
  `contentobject_id` int(11) NOT NULL,
  `contentobject_attribute_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id_session_key` ( `user_id`,`session_key` ),
  KEY `contentobject_id_contentobject_attribute_id` ( `contentobject_id`, `contentobject_attribute_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
