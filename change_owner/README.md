# Change Owner

## Why would you use this?

To update list owner using either a Talis Global User ID or an email address.

## Description

* If supplied with Talis Global User ID this tool simply uses that ID to update the corresponding list.
* If run using email addresses in the upload file and "Update owner using user email" is checked, email addresses will be used to search users and the found user's Talis Global User ID will be used.

No updates will be applied if:
* The Talis Global User ID is not valid
* A Talis user cannot be found for the supplied email address

### Defaults

* The tool runs without updating live data
* The tool runs expecting Talis Global User ID in the upload file

### Log output

The output log will include, in tab delimited format:
* List ID
* List link
* Old owner name
* Old owner ID
* New owner name
* New owner ID
* Outcome

## What data do you need?

A csv file containing:
* List ID

and for the new owner either:
* Talis Global User ID (found in a downloaded All User Profiles report)
or
* Email Address

The file _must_ use **either** Talis Global User ID **or** email address for the new owner. Mixing new owner data will cause errors.

### Examples

##### Talis GUID
```
50BC28F8-8A99-C86D-6103-02C92C013576,cRHWRAzBiWL9zuLf_OYyjg
C06FAD0A-98FE-A978-8A22-F91564A47752,G0_wAkt-9r2-SMs6BjItxQ
004893EC-E4A1-A971-007E-45DF0E21F8B9,FmThfd9Ars0OPA84D_XF-w
78350744-DA50-3CB5-FA47-2DF30ABBD934,-_mVRbmsU3IbJGeSuNDG7A
```
##### Email
```
50BC28F8-8A99-C86D-6103-02C92C013576,a.bcdefg@example.ac.uk
C06FAD0A-98FE-A978-8A22-F91564A47752,b.cdefgh@example.ac.uk
004893EC-E4A1-A971-007E-45DF0E21F8B9,c.defghi@example.ac.uk
78350744-DA50-3CB5-FA47-2DF30ABBD934,d.efghij@example.ac.uk
```
## Considerations

* New owner must have a user profile in Talis
* Talis Global User ID is more reliable and is recommended if the email address in your Talis user profiles is not controlled (using [Automatic Profile Creation](https://support.talis.com/hc/en-us/articles/360002537997-Automatic-Profile-Creation-in-Talis-Aspire-Reading-Lists) for example).
* When updating list owner using email it is possible more than one account will be found during the search of users. In this instance, the first profile that the email address matches will be used, but if this occurs the new owner will be flagged with an asterisk (*) after the name in the logs to enable checking.
