/*
 * (c) 2018: 975l <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for user_transactions
-- ----------------------------
-- DROP TABLE IF EXISTS `user_transactions`;

CREATE TABLE `user_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(48) DEFAULT NULL,
  `credits` smallint(5) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_ip` varchar(48) DEFAULT NULL,
  `creation` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
