# Canon Lore 2.0: API

This is the REST API for the new Canon Lore.  Using this API, you can query the active Canon Lore award database and get a useful subset of the data.

## /person

Gets information about people who have received Lochacian awards and/or lived in Lochac at some time.  By default this doesn't include people like foreign royalty who are only in the database because they gave awards to Lochacians, but it's still possible to refer to those if you know their IDs.

### /person/list

List all the relevant people.  Eventually, this will include filtering, but for now it's just a dump of all who aren't marked as banished or foreign royalty.

### /person/info

List the names and other personal information about a specific person.

#### Query: {id: INT, authorisation: STRING}

* **id**: a numeric ID for a person
* **authorisation**: *(TODO)* a token authorising the request to include extra personal information

#### Result: {sca_name: STRING, branch: {id: INT, name: STRING, type: STRING}, mundane_name: STRING, tracking: STRING}

* **sca_name**: the person's primary SCA name
* **branch.id**: the ID number of the person's home branch
* **branch.name**: the name of the person's home branch
* **branch.type**: the type of the branch: 'Canton', 'College', 'Shire', 'Barony', 'Crown Principality', 'Principality', 'Kingdom', 'Region', 'Hamlet', 'Stronghold', 'Port', 'Palatine Barony'
* **mundane_name**: *(if authorised)* the person's mundane name, if known
* **tracking**: *(if authorised)* a code indicating if we track awards: 'normal' means they can be recommended for an award, 'will-refuse' means they're on record as refusing awards, 'foreign-royalty' means they're only listed because we need foreign royalty's names when they give awards to locals, and 'banished' means they've been banished and are therefore normally hidden from view.

### /person/awards

List all awards received by a specific person.

#### Query: {id: INT}

* **id**: a numeric ID for a person

#### Result: {sca_name: STRING, precedence: INT, received: [{award: {id: INT, name: STRING, acronym: STRING}, date: INT, reign: {id: INT, name: STRING}, event: {id: INT, name: STRING, location: STRING}, branch: {id: INT, name: STRING, type: STRING}}, ...]}

* **sca_name**: the person's SCA name
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

#### Query: {id: INT}

* **id**: a numeric ID for a person

#### Result: {sca_name: STRING, reign: [{}, ...]}

* TODO: complete
