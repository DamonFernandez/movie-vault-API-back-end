CREATE TABLE IF NOT EXISTS `toWatchList` (
    `toWatchListID` int(11) NOT NULL AUTO_INCREMENT,
    `movieID` int(11) NOT NULL,
    `userID` int(11) NOT NULL,
    `priority` int(11) NOT NULL,
    `notes` text NOT NULL,
    PRIMARY KEY (`toWatchListID`),
    FOREIGN KEY (`userID`) REFERENCES `users` (`userID`),
    FOREIGN KEY (`movieID`) REFERENCES `movies` (`movieID`)


) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

