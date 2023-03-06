# Documentation for TALIS API New Acquisitions Tool

Talis Aspire/Alma API to write a ‘new acquisitions’ list to a reading list

__Please note, before using this tool check with Talis whether you require an additional scope.  You will need to add an additional scope for the “make resource” function in the tool.__

## USING ALMA ANAYTICS

Use the following analytics report:

New Acquisitions – TARL API in 

/shared/Community/Reports/Institutions/44WST

Copy this report to your own shared folder.

You will need to construct an API call.  An outline of how to do this can be found here:

From <https://developers.exlibrisgroup.com/blog/how-to-use-an-api-to-retrieve-an-alma-analytics-report/>

Based on the documentation an example of the call broken into its four parts would be:

| domain | Additional analytics info | Report path/name | List limit and key |
| --------| --------| -------- | -------- |
| https://api-eu.hosted.exlibrisgroup.com | /almaws/v1/analytics/reports?path=%2F | path=%2Fshared%2Funiversity%20of%20Westminster%2FReports%2Fcontent%20and%20Digital%20Services%2Fnew%20Acquisitions%20-%20TARL%20API | &limit=25&col_names=true&apikey=l**************************************** |

Which joined together would be:

<https://api-eu.hosted.exlibrisgroup.com/almaws/v1/analytics/reports?path=%2Fshared%2Funiversity%20of%20Westminster%2Freports%2Fcontent%20and%20Digital%20Services%2Fnew%20Acquisitions%20-%20TARL%20API&limit=25&col_names=true&apikey=l7************************>

Take path, and add to $alma_lookup in your user.config.php file

example <br>
//Alma API call to analytics report

$alma_lookup = ‘https://api-eu.hosted.exlibrisgroup.com/almaws/v1/analytics/reports?path=%2Fshared%2Funiversity%20of%20Westminster%2Freports%2Fcontent%20and%20Digital%20Services%2Fnew%20Acquisitions%20-%20TARL%20API&limit=25&col_names=true&apikey=l*************************’;
?>

The column values are as follows:

\<Column0>0<\/Column0><br>
\<Column1> full_name<br>
\<Column3> resource_type<br>
\<Column4> lcn<br>
\<Column5> publisher_name<br> 
\<Column6> title<br>
\<Column7> web_addresses<br>
\<Column8> isbn

These will write to the relevant fields in a Talis Aspire Reading List record.

__Please note, the report in the shared folder is for recent acquisitions.  However, this report can be changed to produce a wide range of reports.  The key thing to remember is the importance of retaining the same values in the same columns.  If you wish to change column positions or values, it would require changes to the script.  If you are uncertain of how to do this it is recommended you speak to the Talis Developer Community.__

## IMPORTANCE

You can also add ‘importance’ values to your records. The ‘importance’ values for a record are unique to each institution. 

To find the importance you wish to apply to the imported records do the following.

- Bring up a TARL record with a specific ‘importance’ applied.
- Right click and select ‘inspect’.
- Change the ‘importance’
- Then in ‘headers’ : ‘request payload’ the relevant identifier will be visible.

![Screenshot from Aspire](/new_acq/exampleImage.png)

Add to **functions.php**

```php
# insert your own url in "id"
$input_imp= ' {
  "data": {
    "id": "' . $input_item . '",
    "type": "items",
    "relationships": {
      "importance": {
        "data": {
          "id": "http://readinglists.westminster.ac.uk/config/importance53fdf54c4f1c0",
          "type": "importances"
        }
      }
    }
  },
  "meta": {
    "list_id": "' . $listID .'",
    "list_etag": "' . $etag . '"
  }
}';

```

## USING A TAB DELIMITED FILE (txt)

Please structure your input file like so:

author->edition->resource_type->lcn->publisher->title->web_addresses->isbn

examples

```
Cope, Mick  Third edition. 	Book 	997210320003711 	Financial Times Prentice Hall 	The seven Cs of consulting : the definitive guide to the consulting process /	  https://ebookcentral.proquest.com/lib/westminster/detail.action?docID=6401406	273731092
```

```
Frost, Anthony 	Third edition	Book	 997211720103711	 Palgrave	Improvisation in drama, theatre and performance : history, practice, theory https://www.vlebooks.com/vleweb/product/openreader?id=WestminUni&isbn=9781137348128	
1137348127
```






