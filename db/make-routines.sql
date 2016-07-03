DELIMITER $$

CREATE PROCEDURE get_award(IN i_id INTEGER)
BEGIN
    SELECT name,
           acronym,
           description,
           rank,
           precedence,
           active,
           branch_id,
           branch_name
      FROM awards a
           LEFT OUTER JOIN branches b ON b.id = a.branch_id
     WHERE a.id = i_id;

    SELECT received_date,
           person_id,
           sca_name,
           event_id,
           event_name,
           reign_id,
           short_name,
           highest_award,
           award_name AS highest_award,
           op_order+1 AS `order`,
           op_count AS `count`
      FROM canon_received
           LEFT OUTER JOIN canon_name ON name_person = received_person AND name_is_primary = 1
           LEFT OUTER JOIN canon_event ON event_id = received_event
           LEFT OUTER JOIN canon_hats ON hats_id = received_hats
           LEFT OUTER JOIN canon_op ON op_person = received_person
           LEFT OUTER JOIN canon_award ON award_id = op_award
     WHERE received_award = i_id
  ORDER BY received_date ASC;
END

$$


CREATE PROCEDURE `get_award_list`()
BEGIN
    SELECT award_id AS id,
           award_name AS name,
           award_acronym AS acronym,
           award_branch AS branch_id,
           branch_name
      FROM canon_award
           LEFT OUTER JOIN canon_branch ON branch_id = award_branch
  ORDER BY name ASC;
END

$$


CREATE PROCEDURE `get_award_name`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT award_name AS name
      FROM canon_award
     WHERE award_id = i_id;
END

$$


CREATE PROCEDURE `get_branch`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT branch_id AS id,
           branch_name AS name,
           branch_location AS location,
           branch_url AS url
      FROM canon_branch
     WHERE branch_id = i_id;
    SELECT history_from AS `from`,
           history_to AS `to`,
           history_type AS `type`,
           history_parent AS parent_id,
           branch_name AS parent_name
      FROM canon_history
           LEFT OUTER JOIN canon_branch ON branch_id = history_parent
     WHERE history_branch = i_id
  ORDER BY history_from ASC;
    SELECT history_branch AS id,
           branch_name AS name,
           history_type AS `type`
      FROM canon_history
           LEFT OUTER JOIN canon_branch ON branch_id = history_branch
     WHERE history_parent = i_id
       AND history_to IS NULL
  ORDER BY name ASC;
    SELECT person_id AS id,
           name_name AS sca
      FROM canon_person
           LEFT OUTER JOIN canon_name ON name_person = person_id AND name_is_primary = 1
     WHERE person_branch = i_id
  ORDER BY sca ASC;
    SELECT name_person AS id,
           name_name AS sca,
           op_order+1 AS `order`,
           op_count AS `count`,
           op_award AS highest_id,
           award_name AS highest_award
      FROM canon_person
           LEFT OUTER JOIN canon_name ON name_person = person_id AND name_is_primary = 1
           LEFT OUTER JOIN canon_op ON op_person = person_id
           LEFT OUTER JOIN canon_award ON award_id = op_award
     WHERE person_branch = i_id
       AND op_order IS NOT NULL
  ORDER BY op_order ASC;
    SELECT event_id AS id,
           event_name AS name,
           event_location AS location,
           event_from AS `from`,
           event_to AS `to`
      FROM canon_event
     WHERE event_host = i_id
  ORDER BY event_from ASC;
    SELECT hats_id AS id,
           hats_short AS name,
           hats_from AS `from`,
           hats_to AS `to`,
           sov.hat_title AS sov_title,
           sov.hat_person AS sov_id,
           sovnm.name_name AS sov,
           con.hat_title AS con_title,
           con.hat_person AS con_id,
           connm.name_name AS con
      FROM canon_hats
           LEFT OUTER JOIN canon_hat sov ON sov.hat_hats = hats_id AND sov.hat_type = 'sovereign'
           LEFT OUTER JOIN canon_name sovnm ON sovnm.name_person = sov.hat_person AND sovnm.name_is_primary = 1
           LEFT OUTER JOIN canon_hat con ON con.hat_hats = hats_id AND con.hat_type = 'consort'
           LEFT OUTER JOIN canon_name connm ON connm.name_person = con.hat_person AND connm.name_is_primary = 1
     WHERE hats_branch = i_id
  ORDER BY hats_from;
END

$$


CREATE PROCEDURE `get_branch_list`()
BEGIN
    SELECT branch_id AS id,
           branch_name AS name,
           history_type AS `type`
      FROM canon_branch
           LEFT OUTER JOIN canon_history ON history_branch = branch_id AND history_to IS NULL
  ORDER BY name ASC;
END

$$


CREATE PROCEDURE `get_branch_name`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT branch_name AS name
      FROM canon_branch
     WHERE branch_id = i_id;
END

$$


CREATE PROCEDURE `get_event`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT event_from AS `from`,
           event_to AS `to`,
           event_location AS location,
           event_host AS `host_id`,
           branch_name AS `host_name`
      FROM canon_event
           LEFT OUTER JOIN canon_branch ON branch_id = event_host
     WHERE event_id = i_id;
    SELECT DISTINCT
           received_hats AS hats_id,
           hats_short AS hats_name
      FROM canon_received
           LEFT OUTER JOIN canon_hats ON hats_id = received_hats
     WHERE received_event = i_id;
    SELECT received_date AS `date`,
           received_person AS person_id,
           name_name AS person_name,
           award_id,
           award_name
      FROM canon_received
           LEFT OUTER JOIN canon_name ON name_person = received_person AND name_is_primary = 1
           LEFT OUTER JOIN canon_award ON award_id = received_award
     WHERE received_event = i_id
  ORDER BY received_date ASC, name_name ASC;
END

$$


CREATE PROCEDURE `get_event_list`()
BEGIN
    SELECT event_id AS id,
           TRIM(event_name) AS name,
           event_from AS `from`,
           event_to AS `to`
      FROM canon_event
  ORDER BY event_from ASC;
END

$$


CREATE PROCEDURE `get_event_name`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT event_name AS name
      FROM canon_event
     WHERE event_id = i_id;
END

$$


CREATE PROCEDURE `get_person`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT person_id AS id,
           person_mundane AS mundane,
           name_name AS sca,
           branch_name,
           branch_id,
           person_tracking AS tracking,
           op_order+1 AS `order`,
           op_award,
           op_count,
           award_name AS highest_award
      FROM canon_person
           LEFT OUTER JOIN canon_name ON name_person = person_id AND name_is_primary = 1
           LEFT OUTER JOIN canon_branch ON branch_id = person_branch
           LEFT OUTER JOIN canon_op ON op_person = person_id
           LEFT OUTER JOIN canon_award ON award_id = op_award
     WHERE person_id = i_id;
    SELECT name_name AS sca,
           name_loar_date AS registered,
           name_lroa_id AS lroa,
           name_is_primary AS `primary`
      FROM canon_name
     WHERE name_person = i_id;
    SELECT DISTINCT
           award_name AS award,
           award_id,
           received_date AS `date`,
           hats_short AS hats,
           hats_id,
           event_name AS event,
           event_id
      FROM canon_received
           LEFT OUTER JOIN canon_award ON award_id = received_award
           LEFT OUTER JOIN canon_hats ON hats_id = received_hats
           LEFT OUTER JOIN canon_event ON event_id = received_event
     WHERE received_person = i_id
  ORDER BY received_date ASC, award_name ASC;
    SELECT me.hat_title AS title,
           you.hat_person AS consort_id,
           name_name AS consort,
           hats_branch AS realm_id,
           branch_name AS realm,
           hats_id AS id,
           hats_from AS `from`,
           hats_to AS `to`
      FROM canon_hat me
           LEFT OUTER JOIN canon_hat you ON you.hat_hats = me.hat_hats AND you.hat_person <> me.hat_person
           LEFT OUTER JOIN canon_name ON name_person = you.hat_person AND name_is_primary = 1
           LEFT OUTER JOIN canon_hats ON hats_id = me.hat_hats
           LEFT OUTER JOIN canon_branch ON branch_id = hats_branch
     WHERE me.hat_person = i_id;
END

$$


CREATE PROCEDURE `get_person_list`()
BEGIN
    SELECT person_id AS id,
           this.name_name AS sca,
           IF(this.name_is_primary = 0, really.name_name,NULL) AS really,
           branch_id,
           branch_name AS branch
      FROM canon_person
           LEFT OUTER JOIN canon_name this
                        ON this.name_person = person_id
           LEFT OUTER JOIN canon_name really
                        ON really.name_person = person_id
                       AND really.name_is_primary = 1
           LEFT OUTER JOIN canon_branch
                        ON branch_id = person_branch
     WHERE this.name_name IS NOT NULL
  ORDER BY this.name_name ASC;
END

$$


CREATE PROCEDURE `get_person_name`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT name_name AS name
      FROM canon_name
     WHERE name_person = i_id
       AND name_is_primary = 1;
END

$$


CREATE PROCEDURE `get_reign`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT history_type AS `type`,
           hats_won AS won,
           hats_from AS `from`,
           hats_to AS `to`,
           hats_branch AS realm_id,
           branch_name AS realm_name,
           sov.hat_title AS sov_title,
           sov.hat_person AS sov_id,
           sovnm.name_name AS sov,
           con.hat_title AS con_title,
           con.hat_person AS con_id,
           connm.name_name AS con
      FROM canon_hats
           LEFT OUTER JOIN canon_history ON history_branch = hats_branch
                                        AND hats_from >= history_from
                                        AND (hats_to <= history_to OR history_to IS NULL)
           LEFT OUTER JOIN canon_branch ON branch_id = hats_branch
           LEFT OUTER JOIN canon_hat sov ON sov.hat_hats = hats_id AND sov.hat_type = 'sovereign'
           LEFT OUTER JOIN canon_name sovnm ON sovnm.name_person = sov.hat_person AND sovnm.name_is_primary = 1
           LEFT OUTER JOIN canon_hat con ON con.hat_hats = hats_id AND con.hat_type = 'consort'
           LEFT OUTER JOIN canon_name connm ON connm.name_person = con.hat_person AND connm.name_is_primary = 1
     WHERE hats_id = i_id;
    SELECT received_date AS `date`,
           received_person AS person_id,
           name_name AS person_name,
           award_id,
           award_name,
           event_id,
           event_name
      FROM canon_received
           LEFT OUTER JOIN canon_name ON name_person = received_person AND name_is_primary = 1
           LEFT OUTER JOIN canon_award ON award_id = received_award
           LEFT OUTER JOIN canon_event ON event_id = received_event
     WHERE received_hats = i_id
  ORDER BY received_date ASC, name_name ASC;
END

$$


CREATE PROCEDURE `get_reign_list`()
BEGIN
    SELECT hats_id AS id,
           hats_short AS name,
           hats_from AS `from`,
           hats_to AS `to`,
           branch_name AS realm,
           history_type AS `type`
      FROM canon_hats
           LEFT OUTER JOIN canon_branch ON branch_id = hats_branch
           LEFT OUTER JOIN canon_history ON history_branch = branch_id AND history_to IS NULL
  ORDER BY branch_name ASC, hats_from ASC;
END

$$


CREATE PROCEDURE `get_reign_name`(
    IN              i_id            INTEGER
)
BEGIN
    SELECT hats_short AS name
      FROM canon_hats
     WHERE hats_id = i_id;
END

$$


