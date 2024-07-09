-- Schema RAPID
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `RAPID` DEFAULT CHARACTER SET utf8mb3 ;
USE `RAPID` ;

-- -----------------------------------------------------
-- Table `RAPID`.`Users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`Users` (
  `UserId` INT NOT NULL AUTO_INCREMENT,
  `UserType` ENUM('Instructor', 'Student') NOT NULL,
  `UserName` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) NOT NULL,
  `PasswordHash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`UserId`),
  UNIQUE INDEX `Email` (`Email` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`Sessions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`Sessions` (
  `SessionId` INT NOT NULL AUTO_INCREMENT,
  `MainInvigilatorId` INT NULL DEFAULT NULL,
  `SessionName` VARCHAR(100) NOT NULL,
  `StartTime` DATETIME NOT NULL,
  `EndTime` DATETIME NOT NULL,
  `Duration` INT NULL DEFAULT NULL,
  `BlacklistedApps` TEXT NULL DEFAULT NULL,
  `WhitelistedApps` TEXT NULL DEFAULT NULL,
  `CreatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`SessionId`),
  INDEX `MainInvigilatorId` (`MainInvigilatorId` ASC) ,
  CONSTRAINT `Sessions_ibfk_1`
    FOREIGN KEY (`MainInvigilatorId`)
    REFERENCES `RAPID`.`Users` (`UserId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`Students`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`Students` (
  `StudentId` INT NOT NULL AUTO_INCREMENT,
  `StudentName` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) NOT NULL,
  `SessionId` INT NULL DEFAULT NULL,
  PRIMARY KEY (`StudentId`),
  UNIQUE INDEX `Email` (`Email` ASC) ,
  INDEX `SessionId` (`SessionId` ASC) ,
  CONSTRAINT `Students_ibfk_1`
    FOREIGN KEY (`SessionId`)
    REFERENCES `RAPID`.`Sessions` (`SessionId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`Screenshots`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`Screenshots` (
  `ScreenshotId` INT NOT NULL AUTO_INCREMENT,
  `StudentId` INT NULL DEFAULT NULL,
  `SessionId` INT NULL DEFAULT NULL,
  `ScreenshotURL` VARCHAR(255) NOT NULL,
  `CapturedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `FlagDescription` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`ScreenshotId`),
  INDEX `StudentId` (`StudentId` ASC) ,
  INDEX `SessionId` (`SessionId` ASC) ,
  CONSTRAINT `Screenshots_ibfk_1`
    FOREIGN KEY (`StudentId`)
    REFERENCES `RAPID`.`Students` (`StudentId`),
  CONSTRAINT `Screenshots_ibfk_2`
    FOREIGN KEY (`SessionId`)
    REFERENCES `RAPID`.`Sessions` (`SessionId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`SessionInvigilators`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`SessionInvigilators` (
  `SessionInvigilatorId` INT NOT NULL AUTO_INCREMENT,
  `SessionId` INT NULL DEFAULT NULL,
  `InvigilatorId` INT NULL DEFAULT NULL,
  PRIMARY KEY (`SessionInvigilatorId`),
  UNIQUE INDEX `SessionId` (`SessionId` ASC, `InvigilatorId` ASC) ,
  INDEX `InvigilatorId` (`InvigilatorId` ASC) VISIBLE,
  CONSTRAINT `SessionInvigilators_ibfk_1`
    FOREIGN KEY (`SessionId`)
    REFERENCES `RAPID`.`Sessions` (`SessionId`),
  CONSTRAINT `SessionInvigilators_ibfk_2`
    FOREIGN KEY (`InvigilatorId`)
    REFERENCES `RAPID`.`Users` (`UserId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`Snapshots`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`Snapshots` (
  `SnapshotId` INT NOT NULL AUTO_INCREMENT,
  `StudentId` INT NULL DEFAULT NULL,
  `SessionId` INT NULL DEFAULT NULL,
  `SnapshotURL` VARCHAR(255) NOT NULL,
  `CapturedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `FlagDescription` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`SnapshotId`),
  INDEX `StudentId` (`StudentId` ASC) ,
  INDEX `SessionId` (`SessionId` ASC) ,
  CONSTRAINT `Snapshots_ibfk_1`
    FOREIGN KEY (`StudentId`)
    REFERENCES `RAPID`.`Students` (`StudentId`),
  CONSTRAINT `Snapshots_ibfk_2`
    FOREIGN KEY (`SessionId`)
    REFERENCES `RAPID`.`Sessions` (`SessionId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `RAPID`.`StudentProcesses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAPID`.`StudentProcesses` (
  `ProcessId` INT NOT NULL AUTO_INCREMENT,
  `StudentId` INT NULL DEFAULT NULL,
  `SessionId` INT NULL DEFAULT NULL,
  `ProcessName` VARCHAR(255) NOT NULL,
  `CapturedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `FlagDescription` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`ProcessId`),
  INDEX `StudentId` (`StudentId` ASC) ,
  INDEX `SessionId` (`SessionId` ASC) ,
  CONSTRAINT `StudentProcesses_ibfk_1`
    FOREIGN KEY (`StudentId`)
    REFERENCES `RAPID`.`Students` (`StudentId`),
  CONSTRAINT `StudentProcesses_ibfk_2`
    FOREIGN KEY (`SessionId`)
    REFERENCES `RAPID`.`Sessions` (`SessionId`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;