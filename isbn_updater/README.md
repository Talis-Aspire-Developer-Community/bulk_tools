# ISBN Updater

Given a list of Item ID's, and old and new ISBNs this script will update them.

## Input file

The CSV file will have 3 columns and no header row.

* item_uuid — the id of the item to edit.  This is everything AFTER the `/items/` part of the URL and not including any file extensions.
* old_isbn — __note that this value is not validated__. If the old ISBN is not already present in the item it will not be updated
* new_isbn — The new ISBN value to add

## Update logic

ISBNs can appear on either the primary or secondary parts of the resource and this script will check each to see if it can find the ISBN to update.

ISBNs cannot be added using this script.

ISBNs cannot be removed using this script.