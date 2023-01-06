# Section - Bulk tool

Adds a new section with items to a list.

## Use cases

* Adding the same section with set items to multiple lists
* Highlighting a subject guide at the top of lists for a specific subject
* Highlighting a digital literacy guide.
* Showcasing a set of resources for study skills or data literacy

## Overview

__Important__ You will need to customize this script to suit your purpose. Running it without any customization will either not work or not yield the results you intend and may be time consuming to 'undo'

You will need to update the script to define a section and one or more resources to add to the section. Instructions follow!

## Data Preparation

* Create a bookmark for each of the resources you wish to add to each list.
* You will need the ID of the resource created by this bookmark. You can usually find this by editing the bookmark and looking in the URL. For example:

    ```txt
    # given this URL
    https://yorksj.rl.talis.com/resources/EXAMPLE6-66D9-3C52-7B11-7D0158522F6F.html?edit

    # the resource ID is
    EXAMPLE6-66D9-3C52-7B11-7D0158522F6F
    ```

* Generate a file of list IDs which should have the section added.
  * One TARL list ID per line To get the list ID manipulate the list URL

    ```txt
    # given this URL
    https://yorksj.rl.talis.com/lists/EXAMPLE6-66D9-3C52-7B11-7D0158522F6F

    # the list ID is
    EXAMPLE6-66D9-3C52-7B11-7D0158522F6F
    ```

## Script Preparation

You are advised to use git version control or to make copies of the files you edit BEFORE starting to edit.

in `/section/src/comp.php` around line 237, look for a section of code like this:

```php
// EDIT THE BELOW PLACEHOLDER VALUES TO SET SECTION TITLE AND DESCRIPTION
// 
// Insert desired section title
$section_title = "replace me with section title text";
// Insert desired section description
$section_description = "Replace me with section description's text";
//
// DO NOT EDIT BELOW THIS LINE
```

Update the section title and description as needed.  This wil be used on every list.

Also in `/section/src/comp.php` around line 313, look for a block of code similar to this:

```php
// START OF STRUCTURE-BUILDING AREA - COPY, PASTE OR DELETE BLOCKS BELOW TO BUILD DESIRED STRUCTURE

//**************ITEM_1*****************
$uuid = guidv4();
$resource = "B986A749-F293-3976-40D4-5F616CEAB683"; //GET THIS RESOURCE ID FROM TARL - EDIT RESOURCE URL
$input = itemBody($etag, $listID, $uuid, $uuid_section, $resource);
$etag = itemPost($shortCode, $TalisGUID, $token, $input, $myfile, $uuid, $uuid_section);
//**************************************

//**************ITEM_2******************
$uuid = guidv4();
$resource = "9F64F566-3C07-9EF1-A6E3-77D4D694EBA6";
$input = itemBody($etag, $listID, $uuid, $uuid_section, $resource);
$etag = itemPost($shortCode, $TalisGUID, $token, $input, $myfile, $uuid, $uuid_section);
//**************************************

//**************ITEM_3*****************
$uuid = guidv4();
$resource = "61074635-D0EE-C0B5-DC9F-C0A684820DA4";
$input = itemBody($etag, $listID, $uuid, $uuid_section, $resource);
$etag = itemPost($shortCode, $TalisGUID, $token, $input, $myfile, $uuid, $uuid_section);
//**************************************

// END OF STRUCTURE-BUILDING AREA - DO NOT EDIT DATA BELOW THIS LINE (UNLESS YOU KNOW WHAT YOU ARE DOING) :)
```

As you can see one or more items can be added to the section by repeating the blocks of code.
You will need to repeat this code for each of the items for which you have created resources.

Please note that it is the RESOURCE ID that is used in each of the blocks above.

Items will be added to the section in the order that they are specified here.

## Running the tool

* Go to the html page for the section tool.
* Choose the input file with the List IDs in it.
* Decide if you want the edited list to be published. (existing unpublished changes made by real users will also be published)
* Click 'send'
