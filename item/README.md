# Item - Bulk tool

Adds a new item to the list.

## Use cases

* Adding the same resource to multiple lists
* Highlighting a subject guide at the top of lists for a specific subject
* Highlighting a digital literacy guide.

## Data Preparation

* Create a bookmark for the resource you wish to add to each list.
* You will need the ID of the resource created by this bookmark. You can usually find this by editing the bookmark and looking in the URL.

    ```txt
    # given this URL
    https://yorksj.rl.talis.com/resources/C86AD996-66D9-3C52-7B11-7D0158522F6F.html

    # the resource ID is
    C86AD996-66D9-3C52-7B11-7D0158522F6F
    ```

* Generate a file of list IDs which should have the item added.
  * One TARL list ID per line

## Running the tool

* Choose the input file
* Insert the resource id
* Decide if you want the edited list to be published.
* Click 'send'