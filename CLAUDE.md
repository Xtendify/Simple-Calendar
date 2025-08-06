# Google Calendar Events (Simple Calendar)

**Repository**: google-calendar-events/  
**Type**: Free WordPress Plugin  
**WordPress.org**: [google-calendar-events](https://wordpress.org/plugins/google-calendar-events/)

## Purpose

The core Simple Calendar plugin that adds Google Calendar events to WordPress sites. This is the foundation plugin that all premium add-ons extend.

## Key Features

- Display Google Calendar events on WordPress sites
- Mobile responsive calendar displays
- Beautiful calendar layouts
- Shortcode support for embedding calendars
- Widget support
- Basic event display functionality
- Google Calendar API integration
- WordPress admin interface for calendar management

## Technical Details

- **Main File**: `google-calendar-events.php`
- **Text Domain**: `google-calendar-events`
- **Requirements**: PHP 7.3+, WordPress 4.2+
- **Extensions**: cURL, iconv, JSON, mbstring
- **OAuth Domain**: `https://auth.simplecalendar.io/`

## Architecture

- Core plugin provides base functionality
- Extensible architecture for add-ons
- Uses WordPress plugin standards
- Includes requirements checking system
- Asset management for CSS/JS

## Relationship to Other Plugins

This is the **required foundation** for all Simple Calendar add-ons:
- simple-calendar-google-calendar-pro
- simple-calendar-fullcalendar  
- simple-calendar-appointment
- simple-calendar-blog-feed
- simple-calendar-acf

## Distribution

- Free plugin available on WordPress.org
- Serves as lead generation for premium add-ons
- Drives users to simplecalendar.io for advanced features