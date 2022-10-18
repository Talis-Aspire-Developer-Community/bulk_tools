# Add a paragraph to a list

This tool will add specified text to every list that you supply via the CSV file upload.

## Data preparation

You will need to decide which lists you want to add a paragraph to. Only draft or published lists can be edited.

In the CSV file, you will need to list each list ID that you want to update. 1 list ID per line
You can get the list id by looking in the URLs from the all lists report `https://{basedomain}/lists/{listId}`

## Running the tool

There is no Dry run option.  Running this tool will make changes to your lists.

On the Add Paragraph HTML page in the bulk tools app (accessed through XAMP in your browser):

* You select the CSV file of list IDs. (We recommend a test run with a single test list ID in it.)
* You'll specify the text to use for every paragraph. Check those spellings!
* You will specify if the lists should be published after adding the paragraph. If a list already has other published changes, ALL changes on a list will be published.

You will get an email when the bulk list publish action has completed.

You will be able to see what was updated using the report log file. `report_files/para_output.log`
