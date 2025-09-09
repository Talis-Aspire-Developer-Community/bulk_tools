# Bulk Importance Tool

## Overview

The Bulk Importance Tool allows you to bulk update the importance level of items in your Talis Aspire reading lists based on a CSV file containing item IDs. This tool is particularly useful when you need to update the importance status of multiple items across your collection efficiently.

## What This Script Does

This tool performs the following operations:

1. **File Upload & Processing**: Accepts a CSV file containing item IDs through a web interface
2. **Item Retrieval**: Fetches item details from the Talis API for each item ID
3. **Importance Assignment**: Updates each item with the specified importance level
4. **List Publishing**: Automatically publishes all affected reading lists after updates
5. **Logging**: Creates detailed logs of all operations performed

## File Format Expected

### Input File Requirements

- **File Type**: CSV (Comma Separated Values)
- **File Extension**: `.csv`
- **Max File Size**: 3MB
- **Column Structure**: Single column containing item IDs
- **Header**: No header row required
- **Format**: One item ID per row

### Example CSV Format

```csv
12345678-1234-1234-1234-123456789012
87654321-4321-4321-4321-210987654321
11111111-2222-3333-4444-555566667777
```

## Configuration Requirements

Before using this tool, you must configure your `user.config.php` file with the following variables:

- `$shortCode` - Your Talis tenancy shortcode
- `$clientID` - Your API client ID
- `$secret` - Your API client secret
- `$TalisGUID` - User GUID for API authentication
- `$importanceID` - The importance level ID to assign to items (use the getTenantImportances API route to find out what these will be. <https://rl.talis.com/3/docs#operation/getTenantImportances>)

## How to Use

1. Ensure your `user.config.php` file is properly configured
2. Navigate to the tool's web interface (`imp.html`)
3. Upload a CSV file containing the item IDs you want to update
4. Click "Send" to start the bulk update process
5. Monitor the progress and results on the processing page
6. Check the log file for detailed results

## Output & Logging

The tool generates a log file at `../../report_files/bulkimp_output.log` containing:

- Start and end timestamps
- Input file name
- For each item processed:
  - Item ID
  - List ID
  - Processing outcome
- Summary of lists published

## Important Warnings & Considerations

### ⚠️ Critical Warnings

1. **Configuration Required**: The script will fail if `user.config.php` is not properly configured
2. **API Limits**: Be mindful of API rate limits when processing large files
3. **Execution Time**: Large files may take significant time to process (execution time limit is disabled)
4. **Draft Items**: The script updates draft items, it will fail if you try to update items on Archived lists
5. **List Publishing**: All affected lists will be automatically published after processing on a background list publishing queue that will not affect live edits to lists. This does mean that any lists with other unpublished changes will also be published. You may wish to run a report of any lists with unpublished changes, and verify that you are happy that those changes are published if the same list is also having items updated.

### ⚠️ Data Safety

- **Test First**: Test with a small file of one item on a test list to ensure correct configuration
- **Importance Overwrite**: This tool will overwrite existing importance levels on items

### ⚠️ Technical Requirements

- **PHP Environment**: Requires PHP with cURL extension enabled
- **File Permissions**: Upload directory must be writable (`../uploads/`)
- **Network Access**: Requires internet connectivity to reach Talis APIs
- **Memory**: Large files may require increased PHP memory limits

## Error Handling

The script includes error handling for:

- File upload failures
- API authentication issues
- Invalid item IDs
- Network connectivity problems
- API response errors

Errors are displayed on screen and logged to the output file.

## File Structure

```text
bulk_importance/
├── README.md
├── src/
│   ├── imp.html          # Web interface for file upload
│   ├── comp.php          # Main processing script
│   └── functions.php     # Helper functions
└── uploads/              # Directory for uploaded CSV files
```

## Dependencies

- PHP with cURL extension
- Valid Talis API credentials
- Properly configured `user.config.php` file
- Web server environment

## Troubleshooting

### Common Issues

1. **File Upload Fails**: Check file size (max 3MB) and upload directory permissions
2. **Authentication Errors**: Verify API credentials in `user.config.php`
3. **Item Not Found**: Ensure item IDs are valid and accessible with your credentials
4. **Timeout Issues**: For large files, consider processing in smaller batches

### Debug Information

The script provides detailed output including:

- Token acquisition status
- Configuration values (excluding secrets)
- Processing status for each item
- Error messages with detailed responses

For additional support, check the log files in the `report_files` directory.
