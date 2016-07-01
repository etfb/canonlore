# Canon Lore 2.0

This is Canon Lore 2.0, a rewrite of the Canon Lore database currently in
operation at http://lochac.sca.org/canon, for use as both an API for other
projects and a Wordpress plugin to replace the existing Canon Lore site.

If you have no idea what the above means, this is probably not going to be much
use to you.

The project is produced by Paul Sleigh, aka Eric TF Bat, aka Baron Karl Faustus
von Aachen.  Contact him on bat@flurf.net.

## Process

Generally, you will want to create a database with valid data in it.  Obtain
a dump of Canon Lore from Mortar Herald, and save it as `old-canon.sql`.  Create
a `password.sql` of your own (see below).  Then run the `go.sql` script within
this directory and your database will be created.

Alternatively, see below about the `go-no-fill.sql` script.

Now get to work!

## Files Included

### make-canon.sql

A script of MySQL commands that drop and re-create a database called canon
and a user also called canon, then fills in all the table definitions.

It requires that a variable called `@password` be set, containing the canon
user's MySQL password.  See below about `password.sql`.

### make-routines.sql

A script of MySQL commands that drop and recreate all the necessary stored
procedures and functions that the API and the Wordpress plugin will use.

### fill-from-old-canon.sql

A script that, given the existence of the original Canon Lore tables and their
data (see `old-canon.sql`, below) will fill the new database tables with a fair
approximation of the equivalent content.  Some tweaking is still required, but
it goes most of the way.

### go.sql

A script to run all of the above, including `password.sql` and `old-canon.sql`,
to rebuild from scratch the whole database.  Obviously you don't want to run
this once you've started adding data of your own!

### go-no-fill.sql

A script to run everything except the `old-canon.sql` and
`fill-from-old-canon.sql` scripts.  In other words, it creates the new database
but doesn't fill it.  If you lack permission to access the old data, this is
the go-script for you.

## Files Not Included

To use this project on your local server, you will need the following extra
files, which are not included:

### password.sql

Contains nothing more than the following text:

    SET @password='the password for the canon user on the canon database';

Fill in your own password there.

### old-canon.sql

A dump of the original Canon Lore database.  This is not included here because
it contains a lot of Personally Identifiable Information.  If you don't have
privileges to get this from http://sca.org.au/phpmyadmin, see above about the
`go-no-fill.sql` script
