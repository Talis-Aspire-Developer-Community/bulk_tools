# Change student note

Adds, updates or deletes student notes on items and publishes the items' list.

Typically used to add a student note to items previously added using the Add an Item tool.

## Data preparation

Be absolutely sure that you know you want to make changes to an item's student note as overwritten notes are not recoverable.

The input file is a tab delimited file containing items IDs and corresponding new student note text. Supplying no student note text will delete the current student note.
You can get the item ID by looking at the URL in the All List Items report. You only need the last part of the item url.
`https://{tenancy_domain}/items/{item_id}`

## Running the tool

On the change student note tool page in the bulk tools app (accessed through XAMP in your browser):

* You select the tab delimited file of item IDs and student notes. (We recommend a test run with a single test item ID in it!)
* You will specify if the lists should be published after changing the student note. If a list already has other published changes, ALL changes on a list will be published at the same time.

You will get an email when the bulk list publish action has completed.
