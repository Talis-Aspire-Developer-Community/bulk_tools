# Delete Item

Deletes an item from a list.

Delete means delete. Turn back while you still can!

Typically used when you have used a tool to add an item or paragraph to every list and you now want to remove those items or paragraphs.  In this scenario you can use the output of the previous scripts you ran to determine which item IDs you need to delete.

## Data preparation

Be absolutely sure that you know you want to delete the items you specify in the input file.

The input file is a single item id per line.
You can get the item ID by looking at the URL in the ALl List Items report. You only need the last part of the item url.
`https://{tenancy_domain}/items/{item_id}`

## Running the tool

On the delete item/paragraph tool page in the bulk tools app (accessed through XAMP in your browser):

* You select the CSV file of list IDs. (We recommend a test run with a single test list ID in it!)
* You will specify if the lists should be published after deleting the item. If a list already has other published changes, ALL changes on a list will be published at the same time.

You will get an email when the bulk list publish action has completed.

You will be able to see what was deleted using the report log file `report_files/del_output.log`.
