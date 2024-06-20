CREATE TABLE IF NOT EXISTS `toWatchList` (
    `watchListid` int(11) NOT NULL AUTO_INCREMENT,
    `movieID` int(11) NOT NULL,
    `userID` bigint(20) NOT NULL,
    `priority` int(11) NOT NULL,
    `notes` text NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`userID`) REFERENCES `users` (`user_id`),
    FOREIGN KEY (`movieID`) REFERENCES `movies` (`id`)


) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

