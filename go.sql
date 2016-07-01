-- Password is ignored by git; see README
source password.sql

-- Create Canon Lore database, user and tables
source make-canon.sql

-- Create all stored procs and functions
-- source make-routines.sql

-- Add in the original Canon Lore database -- see README
source old-canon.sql

-- Fill the new tables from the original database, as much as possible
source fill-from-old-canon.sql
