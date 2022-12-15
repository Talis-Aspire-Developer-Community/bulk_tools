# Update List Time Periods

## Why would you use this?

* To update time periods on lists.
  * After rollover, updating the time period for lists where the module has changed the term or semester in which it is run.
  * Consolidating multiple old time periods into a single time period, e.g. Autumn Term 2015, Spring Term 2016, Summer Term 2016 lists get a single 2015-16 Session time period.
* To list your tenancy's active time periods.

Usage will vary depending on how you use time periods in Talis. Tenancies that have more granular time periods within an academic year may benefit more from this tool.

## Description

* Using supplied time period names, the tool fetches the time period id and uses this to update the time period on a list.
* In **List active time period mode**, the tool ignores any uploaded files and makes no changes to tenancy data, instead it lists all the active time period names on your tenancy.

No updates will be applied if:
* An ID cannot be found for the supplied time period.
* The supplied time period is inactive, and **Include inactive time periods?** option is not ticked.

### Defaults

* The tool runs without updating live data
* The tool runs updating with active time periods only

### Log output

The output log will include, in tab delimited format:
* List ID
* List link
* Old Time Period
* New Time Period
* Outcome

## What data do you need?

A csv file containing:
* List ID
* Time Period Name

The time period name is the human friendly name you see on lists and in Talis reports and is also output in List active time period mode.

### Example
```
50BC28F8-8A99-C86D-6103-02C92C013576,2022-23 Session
C06FAD0A-98FE-A978-8A22-F91564A47752,Autumn Term 2022
004893EC-E4A1-A971-007E-45DF0E21F8B9,Semester 1
78350744-DA50-3CB5-FA47-2DF30ABBD934,2015-16 Session
```
## Considerations

* Time period names are case insensitive for the purposes of searching for time period IDs.
* Draft lists that have never been published cannot be updated using this tool.
