# Reset Student Numbers

In Talis Aspire student numbers on a node are not automatically propagated to the list in order to protect any values that might have been set specifically for the list.

However, if you are sure that you want the student number in the hierarchy to replace the student numbers associated withe the list, then you can use this tool.

For a given list (supplied in the CSV file) This tool will:

* Remove the hierarchy nodes associated with the lists.
* Add the same hierarchy nodes back again - at which point the hierarchy node student numbers _will_ replace any overrides set manually on the list. But only if the hierarchy node has a student number value greater than zero.
* There is an option to also set list student numbers to 0 if the hierarchy node is also 0 and the list previously had a value greater than zero.

## Workflow

* Identify which lists you want to update
* Create a CSV file with the list guid. i.e. the identifier in the last part of the list URL.
* Run a bulk hierarchy import job to ensure your student numbers are correct.
* Run this bulk tool to reset the student numbers on the lists you have identified.

## Limitations

Only Published and Draft lists can be edited in this way.
