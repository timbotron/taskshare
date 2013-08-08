SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `taskshare_db` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `taskshare_db` ;

-- -----------------------------------------------------
-- Table `taskshare_db`.`boards`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `taskshare_db`.`boards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(80) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `taskshare_db`.`tasklists`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `taskshare_db`.`tasklists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `boardid` INT UNSIGNED NOT NULL ,
  `listname` VARCHAR(80) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_tasklists_boards`
    FOREIGN KEY (`boardid` )
    REFERENCES `taskshare_db`.`boards` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_tasklists_boards_idx` ON `taskshare_db`.`tasklists` (`boardid` ASC) ;


-- -----------------------------------------------------
-- Table `taskshare_db`.`tasks`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `taskshare_db`.`tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tasklistid` INT UNSIGNED NOT NULL ,
  `taskname` VARCHAR(199) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_tasks_tasklists1`
    FOREIGN KEY (`tasklistid` )
    REFERENCES `taskshare_db`.`tasklists` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_tasks_tasklists1_idx` ON `taskshare_db`.`tasks` (`tasklistid` ASC) ;

USE `taskshare_db` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
