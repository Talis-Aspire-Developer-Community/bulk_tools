# LCN Updater

Given a list of Item ID's, and old and new Local Control Numbers (LCNs) this script will update the LCN and either add or remove it depending on the presence of values in the third CSV column.

## Input file

The CSV file will have 3 columns and no header row.

* item_guid — the id of the item to edit.  This is everything AFTER the `/items/` part of the URL and not including any file extensions.
* old_lcn — __note that this value is not checked__. If the `new_lcn` LCN is not already present in the item it will be added anyway and ANY existing LCN will be replaced)
* new_lcn — The new value to add - can be letters and numbers and some punctuation marks.

## Update logic

Update of the item is based on the presence of values in the CSV columns. You can have any combination of addition, update or removal rows in your CSV file.

|Action|item_guid|old_lcn|new_lcn|
|--|--|--|--|
|Update LCN|Yes|Yes|Yes|
|remove LCN|Yes|Yes|No|
|Add LCN|Yes|No|Yes|

