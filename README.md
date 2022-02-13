# Sheets Database

Ever wondered if you could use Google Sheets as a datastore for stuff?

Ever wondered if you could use it as a backing datastore for a GPS tracker?

## Example code repo

The example code in this repo demonstrates how to read/write from a Google Sheet,
and run a query using Filter(). The example use-case is of a GPS tracker.

## Why not to do it this way...

In theory, this works. In practice, it doesn't. Perhaps there's a way to get
it working using a second sheet with a data query, but at present this solution
requires fetching rowMetadata for every row in the sheet, due to the way the
Sheets API works. 

This means that the server very quickly runs out of memory. Want to filter a 
five-million row datasheet? You're going to have a five-million entry
PHP array to sort through to look for 'true' values which match the Filter.
In short, the way the Sheets API is designed makes this feasable only for
small sheets. I suspect this was deliberate, to stop potentially abusive
uses of the Sheets tool such as this one ;)

Need a free cloud datastore? Instead of doing this, look at MongoDB Atlas.
