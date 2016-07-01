-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE branch_aliases;

INSERT INTO branch_aliases (branch_id, branch_alias)
    SELECT alias_branch,
           alias_name
      FROM canon_alias
  ORDER BY alias_seq;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE awards;

INSERT INTO awards (id, acronym, name, description, rank, precedence, active, conveys, branch_id)
    SELECT award_id,
           award_acronym,
           award_name,
           award_description,
           award_rank + 0,
           award_precedence,
           award_active,
           IF(award_conveys = 0, NULL, award_conveys),
           award_branch
      FROM canon_award
  ORDER BY award_id;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE branches;

INSERT INTO branches (id, branch_name, region, physical_location, became_branch_id, url)
     SELECT branch_id,
            branch_name,
            branch_region + 0,
            branch_location,
            IF(branch_became = 0, NULL, branch_became),
            branch_url
       FROM canon_branch
   ORDER BY branch_id;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE events;

INSERT INTO events (id, branch_id, from_date, to_date, event_name, physical_location)
     SELECT event_id,
            event_host,
            event_from,
            event_to,
            IF(event_name LIKE 'Unknown (% % %)', NULL, event_name),
            IF(event_location = '', NULL, event_location)
       FROM canon_event
   ORDER BY event_id;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE reigns;

INSERT INTO reigns (id, branch_id, short_name, one_person_id, one_title, other_person_id, other_title, won, stepped_up, stepped_down)
     SELECT hats_id,
            hats_branch,
            hats_short,
            IF(sov.hat_person = 0, NULL, sov.hat_person),
            IF(sov.hat_title = '', NULL, sov.hat_title),
            IF(con.hat_person = 0, NULL, con.hat_person),
            IF(con.hat_title = '', NULL, IF(con.hat_title = 'Vicerine', 'Vicereine', con.hat_title)),
            hats_won,
            hats_from,
            hats_to
       FROM canon_hats
            LEFT OUTER JOIN canon_hat sov ON hats_id = sov.hat_hats AND sov.hat_type = 'sovereign'
            LEFT OUTER JOIN canon_hat con ON hats_id = con.hat_hats AND con.hat_type = 'consort'
   ORDER BY hats_id;

-- -----------------------------------------------------------------------------------------------------------------------------------------

-- NB: this table will require hand-editing to correct multiple errors
TRUNCATE TABLE branch_types;

INSERT INTO branch_types (branch_id, branch_type, transition, parent_branch_id)
     SELECT history_branch,
            history_type + 0,
            history_from,
            history_parent
       FROM canon_history
   ORDER BY history_branch, history_from;


-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE person_aliases;

INSERT INTO person_aliases (id, person_id, sca_name, is_primary)
     SELECT name_id,
            name_person,
            name_name,
            name_is_primary
       FROM canon_name
   ORDER BY name_person, name_is_primary DESC, name_name;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE heraldic_registrations;

INSERT INTO heraldic_registrations (person_alias_id, registration_date, kingdom_branch_id, lroa_id)
     SELECT name_id,
            IF(name_loar_date IS NULL, NULL, CONCAT(name_loar_date,'-01')),
            name_loar_kingdom,
            name_lroa_id
       FROM canon_name
      WHERE name_loar_date IS NOT NULL
         OR name_loar_kingdom IS NOT NULL
         OR name_lroa_id IS NOT NULL
   ORDER BY name_person, name_is_primary DESC, name_name;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE op_scores;

INSERT INTO op_scores (score, person_id, award_id, award_count, highest_date)
     SELECT op_order,
            op_person,
            op_award,
            op_count,
            IF(LOCATE('??',op_date) > 0, NULL, op_date)
       FROM canon_op
   ORDER BY op_order;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE people;

INSERT INTO people (id, mundane_name, branch_id, tracking)
     SELECT person_id,
            IF(person_mundane = '', NULL, person_mundane),
            person_branch,
            person_tracking
       FROM canon_person
   ORDER BY person_id;

-- -----------------------------------------------------------------------------------------------------------------------------------------

TRUNCATE TABLE received_awards;

INSERT INTO received_awards (person_id, award_id, event_id, reign_id, received_date)
     SELECT received_person,
            received_award,
            received_event,
            received_hats,
            IF(LOCATE('??',received_date) > 0, NULL, received_date)
       FROM canon_received
   ORDER BY received_person, received_date;

-- -----------------------------------------------------------------------------------------------------------------------------------------

DROP TABLE canon_alias,
           canon_award,
           canon_branch,
           canon_event,
           canon_hat,
           canon_hats,
           canon_history,
           canon_name,
           canon_notes,
           canon_op,
           canon_person,
           canon_received,
           canon_resigned,
           editor_log,
           meta_clap,
           meta_semaphore,
           meta_user;
