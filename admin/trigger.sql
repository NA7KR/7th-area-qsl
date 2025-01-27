DELIMITER $$

CREATE TRIGGER `trg_update_total_cost`
AFTER UPDATE ON `tbl_CardM`
FOR EACH ROW
BEGIN
    IF NEW.`Postal Cost` IS NOT NULL AND NEW.`Other Cost` IS NOT NULL THEN
        UPDATE `tbl_CardM`
        SET `Total Cost` = NEW.`Postal Cost` + NEW.`Other Cost`
        WHERE `ID` = NEW.`ID`;
    END IF;
END
