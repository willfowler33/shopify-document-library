# TCP Document Library - Shopify Theme Files

A Shopify theme section that provides mobile-optimized access to technical PDF documents (TDS/SDS) with automatic Google Sheets sync. Documents are fetched live from your Google Sheet on every page load -- no database or backend needed.

## Features

- **Google Sheets Auto-Sync** - Documents load live from Google Sheets on every page view
- **No Backend Required** - Runs entirely in the browser, no server or database needed
- **Search & Filter** - Full-text search and filtering by category, brand, product system, and document type
- **Mobile-First Design** - Responsive interface optimized for field technicians
- **Shopify Theme Editor** - Configure API key and spreadsheet ID directly in the theme editor
- **Session Caching** - Caches data for 5 minutes to reduce API calls while staying fresh
- **Section & Snippet** - Use as a full section (theme editor) or a render snippet in any template

## Installation (Step by Step)

### Step 1: Get a Google Sheets API Key

You need a free API key from Google so the document library can read your spreadsheet.

1. Go to [Google Cloud Console](https://console.cloud.google.com/) and sign in with your Google account
2. **Create a project:**
   - Click the project dropdown at the top of the page
   - Click **New Project**
   - Name it something like "TCP Document Library"
   - Click **Create**, then select the project
3. **Enable the Google Sheets API:**
   - In the left sidebar, go to **APIs & Services > Library**
   - Search for **Google Sheets API**
   - Click on it, then click **Enable**
4. **Create an API key:**
   - Go to **APIs & Services > Credentials**
   - Click **+ Create Credentials > API Key**
   - Copy the key that appears (starts with `AIza...`)
5. **Restrict the key (recommended):**
   - Click **Restrict Key** in the dialog
   - Under "API restrictions", select **Restrict key**
   - Check **Google Sheets API** only
   - Under "Website restrictions", add your Shopify store domain (e.g. `yourstore.myshopify.com` and your custom domain)
   - Click **Save**

### Step 2: Make Your Google Sheet Public

1. Open your Google Sheet with the document data
2. Click the **Share** button (top right)
3. Click **Change to anyone with the link**
4. Set permission to **Viewer**
5. Click **Done**
6. Copy the **Spreadsheet ID** from the URL:
   `https://docs.google.com/spreadsheets/d/`**`THIS_PART_IS_THE_ID`**`/edit`

### Step 3: Add Files to Your Shopify Theme

#### Option A: Via Shopify Admin (easiest)

1. In your Shopify admin, go to **Online Store > Themes**
2. On your active theme, click the **...** menu and select **Edit code**
3. **Add the CSS file:**
   - In the left sidebar under **Assets**, click **Add a new asset**
   - Choose **Create a blank file**, name it `tcp-documents` with extension `.css`
   - Paste the contents of [assets/tcp-documents.css](assets/tcp-documents.css)
   - Click **Save**
4. **Add the JS file:**
   - Under **Assets**, click **Add a new asset**
   - Choose **Create a blank file**, name it `tcp-documents` with extension `.js`
   - Paste the contents of [assets/tcp-documents.js](assets/tcp-documents.js)
   - Click **Save**
5. **Add the section:**
   - In the left sidebar under **Sections**, click **Add a new section**
   - Name it `tcp-document-library`
   - Replace all the default content with the contents of [sections/tcp-document-library.liquid](sections/tcp-document-library.liquid)
   - Click **Save**
6. **Add the snippet (optional):**
   - Under **Snippets**, click **Add a new snippet**
   - Name it `tcp-document-library`
   - Paste the contents of [snippets/tcp-document-library.liquid](snippets/tcp-document-library.liquid)
   - Click **Save**

#### Option B: Via Shopify CLI

If you use the Shopify CLI, copy the files directly:

```bash
# From this repo's root, copy into your theme directory:
cp assets/tcp-documents.css   /path/to/your-theme/assets/
cp assets/tcp-documents.js    /path/to/your-theme/assets/
cp sections/tcp-document-library.liquid /path/to/your-theme/sections/
cp snippets/tcp-document-library.liquid /path/to/your-theme/snippets/
```

Then deploy with `shopify theme push`.

### Step 4: Add the Document Library to a Page

#### Using the Theme Editor (Recommended)

1. Go to **Online Store > Themes > Customize**
2. In the page selector dropdown (top center), choose the page where you want the library (e.g. a "Documents" page)
3. Click **Add section**
4. Find and select **Document Library**
5. In the section settings panel on the left:
   - Paste your **Google Sheets API Key**
   - Paste your **Spreadsheet ID**
   - Adjust documents per page if desired (default: 30)
   - Optionally set default filters
6. Click **Save**

That's it! The document library will now appear on that page and auto-sync from your Google Sheet.

#### Using a Liquid Snippet (Advanced)

If you want to embed the library in a custom template, use the snippet:

```liquid
{% render 'tcp-document-library',
  api_key: 'AIzaSy...',
  spreadsheet_id: '1kGVVChB5uqT...'
%}
```

With optional pre-filters:

```liquid
{% render 'tcp-document-library',
  api_key: 'AIzaSy...',
  spreadsheet_id: '1kGVVChB5uqT...',
  category: 'Epoxies',
  brand: 'The Concrete Protector',
  document_type: 'TDS',
  per_page: 50
%}
```

### Step 5: Create a Page to Link To (if needed)

If you don't already have a page for the document library:

1. In Shopify admin, go to **Online Store > Pages**
2. Click **Add page**
3. Title it "Document Library" (or whatever you prefer)
4. Leave the content blank -- the section handles everything
5. Under **Template**, select the template where you added the section
6. Click **Save**
7. Add the page to your navigation: **Online Store > Navigation > Main menu > Add menu item**

## Google Sheets Format

All sheets/tabs in the spreadsheet are read automatically. Each sheet should have a header row with these columns:

| Column Name | Required | Description |
|---|---|---|
| Title | Yes | Document title |
| Description | No | Document description |
| Category | No | Product category (e.g. Epoxies, Polyaspartics) |
| Brand | No | Product brand |
| Product System | No | Product system name |
| Document Type | No | TDS, SDS, Marketing, etc. |
| HubSpot File URL | **Yes** | Direct URL to the PDF file |
| HubSpot File ID | No | HubSpot file identifier |
| File Name | No | Original filename |

**Notes:**
- Column headers are matched flexibly -- "Category (Urethanes, Epoxies, Sealers etc)" matches as "Category"
- Rows without a HubSpot File URL are skipped
- You can have multiple tabs/sheets -- they are all combined into one library

## Section Settings (Theme Editor)

| Setting | Description |
|---|---|
| Google Sheets API Key | Your API key from Google Cloud Console |
| Spreadsheet ID | The ID from your Google Sheet URL |
| Documents per page | 10-100, default 30 |
| Default category filter | Pre-filter by category |
| Default brand filter | Pre-filter by brand |
| Default document type filter | Pre-filter by type |

## How It Works

1. When a customer visits the page, the JavaScript fetches your spreadsheet data via the Google Sheets API
2. All sheets/tabs are read and combined into one document list
3. Data is cached in the browser for 5 minutes so repeated visits are fast
4. Fresh data is always fetched in the background to keep things up to date
5. The frontend renders a searchable, filterable, paginated card grid

**No manual sync, no cron jobs, no database** -- just update your Google Sheet and the changes appear automatically.

## File Structure

```
assets/
  tcp-documents.css           # Frontend styles
  tcp-documents.js            # Frontend app (fetches from Google Sheets)
sections/
  tcp-document-library.liquid # Theme editor section with schema settings
snippets/
  tcp-document-library.liquid # Embeddable snippet for any template
```

## Troubleshooting

### Documents not loading

1. Open browser console (F12 > Console tab) and look for error messages
2. Verify your API key is correct (starts with `AIza...`)
3. Ensure the Google Sheets API is enabled in your Google Cloud project
4. Check that the spreadsheet is shared publicly (Anyone with the link > Viewer)
5. Verify the Spreadsheet ID matches what's in your Google Sheet URL
6. If you restricted your API key to specific domains, make sure your Shopify store domain is listed

### "Failed to load documents" error

- Most commonly caused by an incorrect API key or private spreadsheet
- Check the browser console for the specific HTTP status code:
  - **403**: API key is invalid, restricted, or Sheets API not enabled
  - **404**: Spreadsheet ID is wrong or sheet doesn't exist
  - **429**: Too many requests -- wait a minute and reload

### API quota errors

- Google Sheets API free tier allows 300 requests/minute
- The 5-minute session cache prevents excessive requests
- If you hit limits, wait a few minutes and reload

### PDFs not opening

- Verify the HubSpot File URLs in your spreadsheet open correctly when pasted directly in a browser
- Check for extra spaces or line breaks in the URL cells
- Ensure PDFs are publicly accessible (not behind authentication)

### Section not appearing in theme editor

- Make sure the section file is named exactly `tcp-document-library.liquid`
- Make sure it's in the `sections/` folder (not `snippets/`)
- Try refreshing the theme editor

## Requirements

- A Shopify store with theme editing access
- A Google Cloud account with Sheets API enabled (free tier is sufficient)
- A publicly-shared Google Sheet with document metadata

## Changelog

### Version 2.0.0
- Converted from WordPress plugin to Shopify theme files
- Removed backend dependency -- fetches directly from Google Sheets API
- Auto-syncs on every page view with session caching
- Shopify theme editor section settings
- Vanilla JS (no React dependency)

### Version 1.0.0
- Initial WordPress plugin release

## Support

For issues or questions, please contact The Concrete Protector technical support.

## License

GPL v2 or later

## Credits

Developed for The Concrete Protector
