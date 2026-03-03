# Changelog

All notable changes to TCP Document Library will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-18

### Added
- Initial release of TCP Document Library WordPress plugin
- Google Sheets integration for document metadata sync
- HubSpot integration for PDF file hosting
- WordPress native authentication system
- REST API endpoints for document queries
- React-based frontend document browser
- Search functionality across titles and descriptions
- Filter system for categories, brands, and document types
- Mobile-responsive design optimized for field technicians
- Auto-sync on user login feature
- Admin dashboard for viewing all documents
- Settings page for Google Sheets configuration
- Manual sync page with sync statistics
- Shortcode `[tcp_documents]` for embedding on pages
- Support for shortcode attributes (category, brand, document_type, search)
- Database table `wp_tcp_documents` for local caching
- Comprehensive documentation (README.md, INSTALLATION.md)

### Features
- **Document Management**
  - Automatic sync from Google Sheets
  - Support for 100+ documents
  - Unique identification by HubSpot File URL
  - Create/update logic (upsert)
  - Skip rows without HubSpot URL

- **Search & Filtering**
  - Real-time search across title and description
  - Filter by category (Epoxies, Polyaspartics, Urethanes, Sealers, Dyes/Stains, Cementitious)
  - Filter by brand (The Concrete Protector, Match Patch Pro, Scientific Concrete Polishing, Sani-Tred)
  - Filter by document type (TDS, SDS)
  - Combined filters support
  - Clear filters button

- **User Interface**
  - Mobile-first responsive design
  - Card-based document layout
  - Loading states with spinner
  - Empty states with helpful messages
  - Error handling and display
  - Login required modal
  - Results counter

- **Admin Interface**
  - Documents list with statistics
  - Manual sync page with detailed feedback
  - Settings page with test connection
  - Auto-sync toggle
  - Login requirement toggle
  - Last sync timestamp display

- **API Endpoints**
  - GET /tcp-docs/v1/documents - List documents with filters
  - GET /tcp-docs/v1/documents/{id} - Get single document
  - GET /tcp-docs/v1/filters - Get filter options
  - GET /tcp-docs/v1/auth/user - Get current user
  - POST /tcp-docs/v1/admin/sync - Trigger sync
  - POST /tcp-docs/v1/admin/test-connection - Test Google Sheets

- **Security**
  - WordPress nonce verification
  - User capability checks
  - SQL injection protection via prepared statements
  - XSS protection via escaping
  - CORS headers for REST API

### Database Schema
- Created `wp_tcp_documents` table with columns:
  - id (primary key)
  - title, description
  - category, brand, document_type
  - file_name, file_path (legacy)
  - hubspot_file_url (unique identifier)
  - hubspot_file_id
  - uploaded_by
  - created_at, updated_at

### Dependencies
- WordPress 6.0+
- PHP 7.4+
- React 18 (loaded from CDN)
- ReactDOM 18 (loaded from CDN)
- Google Sheets API v4
- HubSpot (for PDF hosting)

### Documentation
- README.md - Main documentation
- INSTALLATION.md - Step-by-step installation guide
- config-example.php - Configuration reference
- Inline code documentation

## [Unreleased]

### Planned Features
- Gutenberg block for document browser
- Offline PWA caching
- Document download tracking
- User favorites/bookmarks
- Recent documents list
- Document view analytics
- Bulk operations in admin
- Import/export functionality
- Multi-language support
- Custom taxonomy support

### Known Issues
- None reported

---

[1.0.0]: https://github.com/willfowler33/cptdssds/releases/tag/v1.0.0
