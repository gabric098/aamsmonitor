SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `aams_events` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `mode` tinyint(4) NOT NULL,
  `aams_event_id` int(10) NOT NULL,
  `aams_program_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `href` varchar(255) NOT NULL,
  `aams_datetime` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `sub_category` varchar(100) NOT NULL,
  `hash` varchar(100) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aams_event_id` (`mode`,`aams_event_id`,`aams_program_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `process_info` (
  `mode` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `last_finish` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 1. if there's no record for the specified process
--    insert the record with status RUNNING and return 1
-- 2. if there's a record with status IDLE
--    update to status RUNNING and return 1
-- 3. if there's a record with status RUNNING
--    just return 0
DROP PROCEDURE IF EXISTS beginProcess;
DELIMITER //
CREATE PROCEDURE beginProcess(in p_mode tinyint(4), out can_run tinyint(1))
  BEGIN
    DECLARE e_mode tinyint(4);
    DECLARE e_status tinyint(4);
    DECLARE no_process_rows BOOLEAN;


    DECLARE cur_get_process CURSOR FOR
      SELECT mode, status FROM  process_info
      WHERE mode = p_mode;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET no_process_rows = TRUE;

    OPEN cur_get_process;
    FETCH cur_get_process INTO e_mode, e_status;
    IF no_process_rows THEN
      INSERT INTO process_info VALUES(p_mode, 1, 0);
      SET can_run = 1;
    END IF;
-- i found record with status idle
    IF (e_status = 0) THEN
      UPDATE
        process_info
      SET status = 1
      WHERE mode = e_mode;
      SET can_run = 1;
    ELSEIF (e_status = 1) THEN
-- i found a recod with status running
      SET can_run = 0;
    END IF;

    CLOSE cur_get_process;
  END
//
DELIMITER ;


DROP PROCEDURE IF EXISTS updateEvent;
DELIMITER //
CREATE PROCEDURE updateEvent(in p_mode tinyint(4),
							 in p_aams_event_id  int(10),
							 in p_aams_program_id  int(10),
							 in p_name  varchar(255),
							 in p_href  varchar(255),
							 in p_aams_datetime  varchar(100),
							 in p_category  varchar(100),
							 in p_sub_category  varchar(100),
							 in p_hash  varchar(100))
BEGIN
	DECLARE e_id int(10);
	DECLARE e_mode tinyint(4);
	DECLARE e_aams_event_id int(10);
	DECLARE e_aams_program_id int(10);
	DECLARE e_name varchar(255);
	DECLARE e_href varchar(255);
	DECLARE e_aams_datetime varchar(100);
	DECLARE e_category varchar(100);
	DECLARE e_sub_category varchar(100);
	DECLARE e_hash varchar(100);
	DECLARE e_status varchar(100);
  DECLARE no_events_rows BOOLEAN;

	DECLARE cur_get_event CURSOR FOR
        SELECT * FROM  aams_events
        WHERE mode = p_mode
        AND aams_event_id = p_aams_event_id
        AND aams_program_id = p_aams_program_id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET no_events_rows = TRUE;

    OPEN cur_get_event;
    FETCH cur_get_event INTO e_id, e_mode, e_aams_event_id, e_aams_program_id, e_name, e_href, e_aams_datetime, e_category, e_sub_category, e_hash, e_status;
      IF no_events_rows THEN
        INSERT INTO aams_events VALUES (NULL, p_mode, p_aams_event_id, p_aams_program_id, p_name, p_href, p_aams_datetime, p_category, p_sub_category, p_hash, 2);
      END IF;
    	-- i found record with the same program and event id, if the record status on db is normal
    	IF (e_status = 0 AND e_hash != p_hash) THEN
    		UPDATE
    			aams_events
    		SET name = p_name,
    			href = p_href,
    			aams_datetime = p_aams_datetime,
    			category = p_category,
    			sub_category = p_sub_category,
    			hash = p_hash,
    			status = 1
    		WHERE id = e_id;
    	END IF;

	CLOSE cur_get_event;
END
//
DELIMITER ;