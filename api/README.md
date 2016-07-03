# Canon Lore 2.0: API

This is the REST API for the new Canon Lore.  Using this API, you can query the active Canon Lore award database and get a useful subset of the data.

## /person

Gets information about people who have received Lochacian awards and/or lived in Lochac at some time.  By default this doesn't include people like foreign royalty who are only in the database because they gave awards to Lochacians, but it's still possible to refer to those if you know their IDs.

### /person/list

List all the relevant people.  Eventually, this will include filtering, but for now it's just a dump of all who aren't marked as banished or foreign royalty.

#### Query
    { aliases: STRING,
      authorisation: STRING }

* **aliases**: how to handle alternate names: 'inline' means a separate result row for every name and alias, with a link to the primary name; 'group' means an array of aliases included in result rows
* **authorisation**: *(TODO)*  a token authorising the request to include extra personal information

#### Result
    [ { id: INT,
        name: STRING,
        mundane_name: STRING,
        really: STRING,
        aliases: [ STRING, ... ] },
      ... ]

For each person (and each alias, if query.aliases = 'inline'):

* **[].id**: the person's ID
* **[].name**: the persons' SCA name
* **[].mundane_name**: *(if authorised)* the person's mundane name
* **[].really**: *(if query.aliases = 'inline')* the person's primary name, if this is an alias
* **[].aliases[]**: *(if query.aliases = 'group')* the list of aliases for this person

### /person/info

List the names and other personal information about a specific person.

#### Query
    { id: INT,
      authorisation: STRING }

* **id**: a numeric ID for a person
* **authorisation**: *(TODO)* a token authorising the request to include extra personal information

#### Result
    { name: STRING,
      aliases: [ STRING, ... ],
      branch: { id: INT,
                name: STRING,
                type: STRING },
      mundane_name: STRING,
      tracking: STRING }

* **name**: the person's primary SCA name
* **aliases[]**: the list of aliases for the person
* **branch.id**: the ID number of the person's home branch
* **branch.name**: the name of the person's home branch
* **branch.type**: the type of the branch: 'Canton', 'College', 'Shire', 'Barony', 'Crown Principality', 'Principality', 'Kingdom', 'Region', 'Hamlet', 'Stronghold', 'Port', 'Palatine Barony'
* **mundane_name**: *(if authorised)* the person's mundane name, if known
* **tracking**: *(if authorised)* a code indicating if we track awards: 'normal' means they can be recommended for an award, 'will-refuse' means they're on record as refusing awards, 'foreign-royalty' means they're only listed because we need foreign royalty's names when they give awards to locals, and 'banished' means they've been banished and are therefore normally hidden from view.

### /person/awards

List all awards received by a specific person.

#### Query
    { id: INT }

* **id**: a numeric ID for a person

#### Result
    { name: STRING,
      precedence: INT,
      received: [ { award: { id: INT,
                             name: STRING,
                             acronym: STRING },
                    date: INT,
                    reign: { id: INT,
                             name: STRING },
                    event: { id: INT,
                             name: STRING,
                             location: STRING },
                    branch: { id: INT,
                              name: STRING,
                              type: STRING } },
                  ... ] }

* **name**: the person's SCA name
* **precedence**: the person's position in the Order of Precedence, where 1 is the highest position

For each award received:
* **received[].award.id**: the ID of the award
* **received[].award.name**: the name of the award
* **received[].award.acronym**: the acronym of the award
* **received[].date**: the date the award was received
* **received[].reign.id**: the ID of the reign under which the award was received
* **received[].reign.name**: the name of the reign, eg Alfar I and Elspeth I
* **received[].event.id**: the ID of the event at which the award was received
* **received[].event.name**: the name of the event
* **received[].event.location**: the ID of the event at which the award was received
* **received[].branch.id**: the ID of the branch hosting the event
* **received[].branch.name**: the name of the branch
* **received[].branch.type**: the type of the branch: 'Canton', 'College', 'Shire', 'Barony', 'Crown Principality', 'Principality', 'Kingdom', 'Region', 'Hamlet', 'Stronghold', 'Port', 'Palatine Barony'

### /person/roles

List the roles a specific person has filled: crown, coronet and baronage.  (And viceroy/vicereine - do they have an adjective?)

#### Query
    { id: INT }

* **id**: a numeric ID for a person

#### Result
    { name: STRING,
      reign: [ { id: INT,
                 name: STRING,
                 place: INT,
                 title: STRING,
                 partner: { id: INT,
                            name: STRING,
                            title: STRING }
                 branch: { id: INT,
                           name: STRING,
                           type: STRING } },
               ... ] }

* **name**: the person's SCA name

For each reign or tenure:

* **reign[].id**: the ID of the reign record
* **reign[].name**: the name of the reign, eg "Alfar I and Elspeth I"
* **reign[].place**: this person's place in the order of the pair: usually 1 for King/Prince/Viceroy/Baron and 2 for Queen/Princess/Vicereine/Baroness, but this can vary in some cases (same-sex reigns, single baronial tenures)
* **reign[].title**: this person's title: King, Queen, Prince, etc
* **reign[].partner.id**: the other person's ID (if not a single tenure)
* **reign[].partner.name**: the other person's SCA name
* **reign[].partner.title**: the other person's title: Queen, King, Princess, etc
* **reign[].branch.id**: the ID of the branch over which they ruled
* **reign[].branch.name**: the name of the branch
* **reign[].branch.type**: the type of the branch: 'Barony', 'Crown Principality', 'Principality', 'Kingdom', 'Palatine Barony'
