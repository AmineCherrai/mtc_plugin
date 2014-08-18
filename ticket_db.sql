SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `ticket_db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `ticket_db` ;

-- -----------------------------------------------------
-- Table `ticket_db`.`mtc_tx`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ticket_db`.`mtc_tx` (
  `card_id` INT NOT NULL AUTO_INCREMENT ,
  `tx_id` INT NOT NULL ,
  `card_amount` FLOAT NOT NULL ,
  `full_name` VARCHAR(100) NOT NULL ,
  `email` VARCHAR(100) NOT NULL ,
  `tel` VARCHAR(45) NOT NULL ,
  `address` VARCHAR(100) NOT NULL ,
  `state` VARCHAR(45) NOT NULL ,
  `city` VARCHAR(45) NOT NULL ,
  `post_code` VARCHAR(45) NOT NULL ,
  `country` VARCHAR(45) NOT NULL ,
  `curency` VARCHAR(4) NOT NULL ,
  `paid` INT(1) NOT NULL DEFAULT 0 ,
  `tx_date` DATETIME NOT NULL ,
  `card_date` DATETIME NOT NULL ,
  PRIMARY KEY (`card_id`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
