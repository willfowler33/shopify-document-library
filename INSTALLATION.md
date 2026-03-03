# Installation Guide - TCP Document Library

Complete step-by-step installation instructions for The Concrete Protector Document Library WordPress plugin.

## Prerequisites

Before installing, ensure you have:

- WordPress 6.0 or higher installed
- PHP 7.4 or higher
- Admin access to your WordPress site
- A Google account with access to Google Cloud Console
- Access to your Google Sheet with document metadata

## Step 1: Set Up Google Sheets API

### 1.1 Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" at the top
3. Click "New Project"
4. Enter project name: "TCP Document Library"
5. Click "Create"

### 1.2 Enable Google Sheets API

1. In the Google Cloud Console, go to "APIs & Services" > "Library"
2. Search for "Google Sheets API"
3. Click on "Google Sheets API"
4. Click "Enable"

### 1.3 Create API Key

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "API Key"
3. Copy the API key (you'll need this later)
4. Click "Restrict Key" (recommended)
5. Under "API restrictions", select "Restrict key"
6. Choose "Google Sheets API" from the dropdown
7. Click "Save"

### 1.4 Prepare Your Google Sheet

1. Open your Google Sheet
2. Make sure it has a tab named **"File Data"** (exact name)
3. The first row should contain these headers:
   - Title
   - Description
   - Category (Urethanes, Epoxies, Sealers etc) - or just "Category"
   - Brand
   - Document Type (SDS, TDS, Marketing) - or just "Document Type"
   - HubSpot File URL *(required)*
   - HubSpot File ID
   - Google Drive File URL *(optional)*
   - File Name
   - Last Updated *(optional)*

   **Note:** Column headers are flexible - the plugin will match even if you include additional descriptive text in parentheses.

4. Make sure the sheet is shared publicly or with your Google account:
   - Click "Share" button
   - Click "Change to anyone with the link"
   - Set to "Viewer"
   - Click "Done"

5. Copy the Spreadsheet ID from the URL:
   ```
   https://docs.google.com/spreadsheets/d/[SPREADSHEET_ID]/edit
   ```

## Step 2: Install the Plugin

### Option A: Upload via WordPress Admin

1. Download the plugin as a ZIP file
2. Log in to your WordPress admin panel
3. Go to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file
6. Click **Install Now**
7. Click **Activate Plugin**

### Option B: Manual Installation via FTP

1. Download the plugin
2. Extract the ZIP file
3. Upload the `tcp-document-library` folder to `/wp-content/plugins/` via FTP
4. Log in to WordPress admin
5. Go to **Plugins**
6. Find "TCP Document Library" and click **Activate**

### Option C: Install via Git (for developers)

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/willfowler33/cptdssds.git tcp-document-library
```

Then activate via WordPress admin.

## Step 3: Configure Plugin Settings

1. In WordPress admin, go to **TCP Documents > Settings**

2. **Google Sheets Integration Section:**
   - **Google Sheets API Key**: Paste the API key from Step 1.3
   - **Spreadsheet ID**: Paste the spreadsheet ID from Step 1.4
   - **Sheet Name**: Enter `File Data` (or your custom tab name)

3. **General Settings Section:**
   - **Auto-sync on Login**: ✓ Checked (recommended)
   - **Require Login**: ✓ Checked (recommended)

4. Click **Save Settings**

## Step 4: Test Connection

1. On the Settings page, scroll to **Test Connection**
2. Click the **Test Connection** button
3. You should see a success message with the number of rows found
4. If you see an error:
   - Verify your API key is correct
   - Check that the spreadsheet ID is correct
   - Ensure the sheet tab is named exactly "File Data"
   - Verify the sheet is publicly accessible

## Step 5: Initial Sync

1. Go to **TCP Documents > Sync Now**
2. Click **Sync Now** button
3. Wait for the sync to complete (may take a few moments)
4. You should see:
   - Created: [number of new documents]
   - Updated: [number of updated documents]
   - Skipped: [number of rows without HubSpot URL]

## Step 6: Verify Installation

1. Go to **TCP Documents > All Documents**
2. You should see all your synced documents
3. Try clicking "View PDF" on any document to verify PDFs are accessible

## Step 7: Create a Page to Display Documents

1. Go to **Pages > Add New**
2. Enter a title: "Document Library"
3. In the content area, add the shortcode:
   ```
   [tcp_documents]
   ```
4. Click **Publish**
5. View the page to see the document library in action

## Advanced Configuration

### Custom Filters

You can create pages with pre-filtered documents:

**TDS Documents Only:**
```
[tcp_documents document_type="TDS"]
```

**Epoxy Products:**
```
[tcp_documents category="Epoxies"]
```

**Specific Brand:**
```
[tcp_documents brand="The Concrete Protector"]
```

**Combined Filters:**
```
[tcp_documents category="Polyaspartics" document_type="SDS"]
```

### Permissions

By default, users must be logged in to view documents. To change this:

1. Go to **TCP Documents > Settings**
2. Uncheck **Require Login**
3. Click **Save Settings**

### Auto-Sync Frequency

Auto-sync triggers when users log in. To disable:

1. Go to **TCP Documents > Settings**
2. Uncheck **Auto-sync on Login**
3. Click **Save Settings**

You'll need to manually sync via **TCP Documents > Sync Now**

## Troubleshooting

### Problem: "Failed to fetch data from Google Sheets"

**Solutions:**
- Verify API key is correct
- Ensure Google Sheets API is enabled in Cloud Console
- Check that spreadsheet is publicly accessible
- Verify spreadsheet ID is correct

### Problem: "No documents found"

**Solutions:**
- Go to **TCP Documents > Sync Now** to sync documents
- Verify your Google Sheet has data
- Check that rows have HubSpot File URL values
- View admin documents list to see if documents are in database

### Problem: Shortcode displays raw text

**Solutions:**
- Ensure plugin is activated
- Clear WordPress cache
- Try a different page/post

### Problem: PDFs won't open

**Solutions:**
- Verify HubSpot File URLs are publicly accessible
- Check browser console for errors
- Try opening URL directly in browser
- Ensure PDF is not password protected

### Problem: Frontend not loading

**Solutions:**
- Clear browser cache
- Check that user is logged in (if required)
- Open browser console to check for JavaScript errors
- Ensure React CDN is accessible

## Testing Checklist

- [ ] Plugin activated successfully
- [ ] Settings saved without errors
- [ ] Connection test passes
- [ ] Initial sync completes successfully
- [ ] Documents visible in admin list
- [ ] Page with shortcode displays document library
- [ ] Search functionality works
- [ ] Filter dropdowns populated
- [ ] Can open PDFs in new tab
- [ ] Mobile view displays correctly

## Next Steps

1. **Customize Appearance**: Modify `/public/css/style.css` to match your branding
2. **Create Menu Item**: Add document library page to your WordPress menu
3. **Set User Roles**: Configure which users can access documents
4. **Train Users**: Show field technicians how to search and filter documents
5. **Monitor Sync**: Check **TCP Documents > Sync Now** for last sync time

## Getting Help

If you encounter issues not covered in this guide:

1. Check the browser console for JavaScript errors
2. Check WordPress debug log for PHP errors
3. Review Google Sheets API quota limits
4. Contact your WordPress administrator
5. Review the main README.md for additional documentation

## Uninstallation

To completely remove the plugin:

1. Go to **Plugins** in WordPress admin
2. Deactivate "TCP Document Library"
3. Click **Delete**
4. Confirm deletion

**Note:** This will remove the custom database table and all synced documents from WordPress (but not from Google Sheets or HubSpot).

## Maintenance

### Regular Tasks

- **Weekly**: Check that auto-sync is working
- **Monthly**: Verify document count matches Google Sheet
- **Quarterly**: Review and update API keys if needed
- **As Needed**: Manual sync after bulk Google Sheet updates

---

**Installation Complete!** Your TCP Document Library is now ready to use.
