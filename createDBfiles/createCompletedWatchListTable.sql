CREATE TABLE IF NOT EXISTS `completedWatchList` (
    `completedWatchListID` int(11) NOT NULL AUTO_INCREMENT,
    `userID` int(11) NOT NULL,
    `movieID` int(11) NOT NULL,
    `rating` int(11) NOT NULL,
    `notes` text NOT NULL,
    `dateStarted` date NOT NULL,
    `dateCompleted` date NOT NULL,
    `numOfTimesWatched` int(11) NOT NULL,
    PRIMARY KEY (`completedWatchListID`),
    FOREIGN KEY (`userID`) REFERENCES `users` (`userID`),
    FOREIGN KEY (`movieID`) REFERENCES `movies` (`movieID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
