# ISBN Updater

Given a list of Item ID's, and old and new ISBN13s this script will update the ISBN13.

This script __ONLY Updates the ISBN13__.

This script has been designed to work for quite a narrow use case, but could be extended in the future to have more functionality.

## Input file

The CSV file will have 3 columns and no header row.

* item_uuid — the id of the item to edit.  This is everything AFTER the `/items/` part of the URL and not including any file extensions.
* old_isbn — __note that this value is not validated__. If the old ISBN13 is not already present in the item it will not be updated
* new_isbn — The new ISBN13 value to add

## Update logic

ISBN13s can appear on either the primary or secondary parts of the resource and this script will check each to see if it can find the ISBN to update.

ISBNs cannot be added using this script.

ISBNs cannot be removed using this script.