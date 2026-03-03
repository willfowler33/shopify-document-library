# TCP Document Library - WordPress Plugin

A WordPress plugin for The Concrete Protector company that provides mobile-optimized access to technical PDF documents (Technical Data Sheets and Safety Data Sheets) for decorative concrete coating products.

## Features

- **Google Sheets Integration** - Sync document metadata automatically from Google Sheets
- **HubSpot Integration** - Store PDFs in HubSpot for CRM integration
- **Search & Filter** - Full-text search and filtering by category, brand, and document type
- **Mobile-First Design** - Responsive interface optimized for field technicians
- **WordPress Authentication** - Uses native WordPress user system
- **Auto-Sync** - Automatically syncs documents when users log in
- **REST API** - RESTful API for document queries
- **React Frontend** - Modern, fast React-based document browser
- **Shortcode Support** - Easy embedding with `[tcp_documents]`

## Installation

### 1. Upload Plugin

1. Download or clone this repository
2. Upload the `tcp-document-library` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### 2. Get Google Sheets API Key

**Important:** You need a Google Sheets API key for the plugin to sync documents.

#### Step-by-Step Instructions:

1. **Go to Google Cloud Console**
   - Visit [https://console.cloud.google.com/](https://console.cloud.google.com/)
   - Sign in with your Google account

2. **Create or Select a Project**
   - Click the project dropdown at the top of the page
   - Click "New Project"
   - Enter a project name (e.g., "TCP Document Library")
   - Click "Create"
   - Wait for the project to be created, then select it

3. **Enable Google Sheets API**
   - In the left sidebar, go to **APIs & Services > Library**
   - Search for "Google Sheets API"
   - Click on "Google Sheets API"
   - Click the **"Enable"** button
   - Wait for it to enable

4. **Create API Credentials**
   - In the left sidebar, go to **APIs & Services > Credentials**
   - Click **"+ Create Credentials"** at the top
   - Select **"API Key"**
   - A dialog will appear with your new API key - **COPY THIS KEY** (you'll need it later)

5. **Restrict the API Key (Recommended for Security)**
   - Click **"Restrict Key"** in the dialog (or click the pencil icon next to your key)
   - Under **"API restrictions"**, select **"Restrict key"**
   - In the dropdown, check **"Google Sheets API"**
   - Click **"Save"**

6. **Make Your Google Sheet Accessible**
   - Open your Google Sheet with document data
   - Click the **"Share"** button
   - Click **"Change to anyone with the link"**
   - Set permission to **"Viewer"**
   - Click **"Done"**

### 3. Configure Plugin Settings

**All settings are configured in the WordPress admin - nothing is hardcoded!**

1. In WordPress admin, go to **TCP Documents > Settings**

2. **Enter Google Sheets API Key:**
   - Paste the API key you copied from Google Cloud Console
   - This is required for the plugin to access your spreadsheet

3. **Enter Spreadsheet ID:**
   - Open your Google Sheet
   - Copy the ID from the URL: `https://docs.google.com/spreadsheets/d/`**`[SPREADSHEET_ID]`**`/edit`
   - Paste it into the Spreadsheet ID field
   - Default example: `1kGVVChB5uqT1RR4cCbYxb97Bu2PyI6tgU8F6BfzCFo0`

4. **Enter Sheet Name:**
   - Enter the name of the tab in your spreadsheet (default: `File Data`)
   - This must match exactly (case-sensitive)

5. **Configure General Settings:**
   - ✓ **Auto-sync on Login** - Automatically sync when users log in (recommended)
   - ✓ **Require Login** - Only logged-in users can view documents (recommended)

6. Click **Save Settings**

### 4. Test Connection

1. On the settings page, click **Test Connection**
2. Verify that the connection is successful

### 5. Sync Documents

1. Go to **TCP Documents > Sync Now**
2. Click **Sync Now** to import documents from Google Sheets
3. Wait for sync to complete

## Usage

### Display Documents on a Page

Use the shortcode to display the document library on any page or post:

```
[tcp_documents]
```

### With Filters

Pre-filter documents using shortcode attributes:

```
[tcp_documents category="Epoxies"]
[tcp_documents brand="The Concrete Protector"]
[tcp_documents category="Polyaspartics" document_type="TDS"]
```

### Available Attributes

- `category` - Filter by category (Epoxies, Polyaspartics, Urethanes, Sealers, Dyes/Stains, Cementitious)
- `brand` - Filter by brand (The Concrete Protector, Match Patch Pro, Scientific Concrete Polishing, Sani-Tred)
- `document_type` - Filter by type (TDS or SDS)
- `search` - Pre-populate search term

## Google Sheets Format

Your Google Sheet should have a tab named "File Data" (or "Sheet1") with these columns:

| Column Name | Required | Description |
|-------------|----------|-------------|
| Title | Yes | Document title |
| Description | No | Document description |
| Category (Urethanes, Epoxies, Sealers etc) | No | Product category |
| Brand | No | Product brand |
| Document Type (SDS, TDS, Marketing) | No | Document type |
| HubSpot File URL | **Yes** | Direct URL to PDF file (unique identifier) |
| HubSpot File ID | No | HubSpot file identifier |
| Google Drive File URL | No | (Optional - not currently used) |
| File Name | No | Original filename |
| Last Updated | No | (Optional - not currently used) |

**Important:**
- The HubSpot File URL is **required** and used as the unique identifier for each document
- Column headers can include additional text in parentheses (e.g., "Category (Urethanes, Epoxies, Sealers etc)")
- The plugin will match columns even if they have extra descriptive text

## Admin Features

### View All Documents

Go to **TCP Documents > All Documents** to see all synced documents with:
- Total document count
- Category and brand statistics
- Filterable document list
- Quick PDF access

### Manual Sync

Go to **TCP Documents > Sync Now** to manually trigger a sync from Google Sheets.

### Auto-Sync

By default, documents are automatically synced in the background when users log in. You can disable this in **Settings**.

## API Endpoints

The plugin provides REST API endpoints at `/wp-json/tcp-docs/v1/`:

### Public Endpoints (require authentication)

- `GET /documents` - List documents with optional filters
  - Query params: `search`, `category`, `brand`, `document_type`, `limit`, `offset`
- `GET /documents/{id}` - Get single document
- `GET /filters` - Get available filter options
- `GET /auth/user` - Get current user info

### Admin Endpoints (require admin privileges)

- `POST /admin/sync` - Trigger Google Sheets sync
- `POST /admin/test-connection` - Test Google Sheets connection

## Requirements

### WordPress Environment
- WordPress 6.0 or higher
- PHP 7.4 or higher
- Active WordPress user accounts (for authentication)

### External Services (Required)
- **Google Cloud Account** - Free account required to obtain API key
- **Google Sheets API Key** - Obtained from Google Cloud Console (see installation instructions above)
- **Google Sheet** - Public spreadsheet with document metadata
- **HubSpot Account** - For hosting PDF files (URLs stored in Google Sheet)

### Important Notes
- ✅ **No coding required** - All configuration is done through WordPress admin
- ✅ **No hardcoded values** - API key and spreadsheet ID are entered in settings
- ✅ **Free API access** - Google Sheets API has a generous free tier
- ✅ **One-time setup** - Configure once, sync automatically

## File Structure

```
tcp-document-library/
├── tcp-document-library.php    # Main plugin file
├── includes/
│   ├── class-database.php      # Database operations
│   ├── class-google-sheets.php # Google Sheets sync
│   ├── class-api.php           # REST API endpoints
│   └── class-shortcode.php     # Shortcode handler
├── admin/
│   ├── class-admin.php         # Admin interface
│   ├── class-settings.php      # Settings management
│   ├── css/
│   │   └── admin.css          # Admin styles
│   ├── js/
│   │   └── admin.js           # Admin scripts
│   └── views/
│       ├── settings-page.php  # Settings template
│       ├── sync-page.php      # Sync template
│       └── documents-list.php # Documents list template
├── public/
│   ├── class-public.php       # Frontend handler
│   ├── css/
│   │   └── style.css          # Frontend styles
│   └── js/
│       └── app.js             # React frontend app
└── README.md
```

## Database Schema

The plugin creates one custom table: `wp_tcp_documents`

### Columns

- `id` - Primary key
- `title` - Document title
- `description` - Document description
- `category` - Product category
- `brand` - Product brand
- `document_type` - TDS or SDS
- `file_name` - Original filename
- `file_path` - Legacy field (unused)
- `hubspot_file_url` - PDF URL (unique identifier)
- `hubspot_file_id` - HubSpot file ID
- `uploaded_by` - WordPress user ID
- `created_at` - Created timestamp
- `updated_at` - Last updated timestamp

## Security

- All API endpoints require user authentication
- Admin endpoints require `manage_options` capability
- Nonce verification on AJAX requests
- SQL injection protection via prepared statements
- XSS protection via WordPress escaping functions

## Troubleshooting

### Google Sheets API Issues

**Problem: "Failed to fetch data from Google Sheets"**

**Solutions:**
1. **Verify API Key is Correct**
   - Go to TCP Documents > Settings
   - Make sure the API key is entered correctly (no extra spaces)
   - API keys start with `AIza...`

2. **Check API is Enabled**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Navigate to APIs & Services > Library
   - Search for "Google Sheets API"
   - Make sure it shows "API enabled"

3. **Verify API Key Restrictions**
   - Go to APIs & Services > Credentials
   - Click on your API key
   - Under "API restrictions", make sure "Google Sheets API" is selected
   - If you have "HTTP referrers" restrictions, your WordPress site domain must be listed

4. **Test the API Key**
   - Go to TCP Documents > Settings
   - Click "Test Connection" button
   - This will verify your API key and spreadsheet access

**Problem: "API key quota exceeded"**

**Solutions:**
- Google Sheets API has a free tier limit of 300 requests per minute
- The plugin caches data locally, so this should rarely happen
- If it does, wait a few minutes and try again
- Consider upgrading to a paid Google Cloud plan for higher limits

### Spreadsheet Access Issues

**Problem: "No data received from Google Sheets"**

**Solutions:**
1. **Verify Spreadsheet ID**
   - Open your Google Sheet in a browser
   - Copy the ID from the URL: `https://docs.google.com/spreadsheets/d/`**`[SPREADSHEET_ID]`**`/edit`
   - Go to TCP Documents > Settings
   - Make sure this ID is entered correctly

2. **Check Sheet is Publicly Accessible**
   - Open your Google Sheet
   - Click "Share" button
   - Make sure it's set to "Anyone with the link" can view
   - If it's private, the API key won't be able to access it

3. **Verify Sheet Tab Name**
   - The plugin looks for a tab named "File Data" by default
   - Check your Google Sheet has a tab with this exact name (case-sensitive)
   - If your tab has a different name, update it in Settings

### Documents Not Syncing

**Problem: Documents aren't appearing after sync**

**Solutions:**
1. **Check Required Columns**
   - Your sheet MUST have a "HubSpot File URL" column
   - This column must have values (rows without URLs are skipped)
   - Verify column headers match: "Title", "Description", "Category (Urethanes, Epoxies, Sealers etc)", etc.

2. **Check Sync Results**
   - Go to TCP Documents > Sync Now
   - Click "Sync Now"
   - Look at the results: Created, Updated, Skipped, Errors
   - If "Skipped" is high, check that rows have HubSpot File URLs

3. **View Admin Documents List**
   - Go to TCP Documents > All Documents
   - This shows what's actually in the database
   - If documents are here but not on frontend, it's a display issue

### PDFs Not Opening

**Problem: "PDF won't open" or "404 error"**

**Solutions:**
1. **Verify HubSpot URLs**
   - Copy a HubSpot File URL from your Google Sheet
   - Paste it directly in a browser
   - It should open the PDF directly
   - If it doesn't work in browser, the URL is incorrect

2. **Check URL Format**
   - HubSpot URLs should look like: `https://XXXXXX.fs1.hubspotusercontent-na1.net/hubfs/XXXXXX/filename.pdf`
   - Make sure there are no extra spaces or line breaks

3. **Browser Console Errors**
   - Open browser console (F12)
   - Click a PDF link
   - Check for CORS errors or 404s
   - This will help identify the issue

### Settings Page Issues

**Problem: "Settings won't save"**

**Solutions:**
1. Make sure you're logged in as an admin
2. Check for WordPress errors in debug log
3. Try deactivating other plugins temporarily
4. Clear browser cache and try again

**Problem: "Test Connection" button does nothing**

**Solutions:**
1. Check browser console for JavaScript errors
2. Make sure jQuery is loaded on the page
3. Try a different browser
4. Check WordPress admin is using HTTPS (mixed content can block AJAX)

## Changelog

### Version 1.0.0
- Initial release
- Google Sheets integration
- HubSpot PDF hosting
- Search and filtering
- REST API
- React frontend
- Auto-sync on login
- WordPress authentication

## Support

For issues or questions, please contact The Concrete Protector technical support.

## License

GPL v2 or later

## Credits

Developed for The Concrete Protector
