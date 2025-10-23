# Schema.org Event Improvements

This document describes the improvements made to resolve Google Search Console structured data warnings for events.

## Problem

Google Search Console was reporting missing required fields for schema.org Event structured data:

- Missing field "eventAttendanceMode"
- Missing field "endDate" 
- Missing field "offers"
- Missing field "image"
- Missing field "eventStatus"
- Missing field "performer"
- Missing field "organizer"
- Missing field "description"

## Solution

### 1. Automatic Schema Properties Addition

The plugin now automatically adds all missing schema.org properties to **ALL events**, regardless of the event template used. This means:

- **Existing calendars** automatically get schema properties without any template updates
- **New calendars** get schema properties through the updated default template
- **Custom templates** automatically get schema properties added

### 2. Added Schema Properties

The following schema.org properties are automatically added as hidden meta tags:

- **eventAttendanceMode**: Automatically set to `OfflineEventAttendanceMode` for events with locations, `OnlineEventAttendanceMode` for events without locations
- **eventStatus**: Set to `EventScheduled` by default
- **offers**: Set as a free event with price "0" and USD currency
- **image**: Uses site logo or site icon as fallback
- **performer**: Uses organizer name if available, otherwise site name
- **organizer**: Ensures organizer is always present, using site name as fallback
- **endDate**: Ensures end date is always present, using start date + 1 hour for events without end dates
- **description**: Uses event title as fallback if description is empty

### 3. Smart Fallback System

The implementation includes intelligent fallbacks:

- **Location-based attendance mode**: Automatically detects online vs offline events
- **End date fallback**: Creates end date for events that don't have one
- **Image fallback**: Uses site logo → site icon → no image (graceful degradation)
- **Performer fallback**: Uses organizer → site name
- **Organizer fallback**: Uses site name and URL
- **Description fallback**: Uses event title if description is empty

## Files Modified

1. **includes/events/event-builder.php**
   - Modified `parse_event_template_tags()` to automatically add schema properties
   - Added `get_missing_schema_properties()` method
   - Added `get_event_image_url()` method  
   - Added `get_event_performer()` method
   - Added `schema-meta` content tag (for manual use)
   - Added case handler for `schema-meta` tag

2. **includes/functions/shared.php**
   - Updated `simcal_default_event_template()` to include `[schema-meta]` tag
   - Added `simcal_update_calendar_templates_for_schema()` utility function

## How It Works

### Automatic Addition
The schema properties are automatically added in the `parse_event_template_tags()` method:

```php
// Add schema properties if not already present in template
if (strpos($template_tags, '[schema-meta]') === false) {
    $result .= $this->get_missing_schema_properties($this->event);
}
```

This ensures that:
- If a template has `[schema-meta]`, it uses that (no duplication)
- If a template doesn't have `[schema-meta]`, it automatically adds the properties
- All events get the required schema properties regardless of template

### Template Compatibility
- **Existing templates**: Automatically get schema properties added
- **Custom templates**: Automatically get schema properties added  
- **Default template**: Includes `[schema-meta]` tag for explicit control

## Usage

### For All Calendars (Automatic)
All calendars automatically get schema properties - no action required!

### For Manual Control
If you want to manually control schema properties in a template, you can:

1. **Add to templates**: Edit your calendar's event template and add `[schema-meta]` at the end
2. **Use utility function**: Call `simcal_update_calendar_templates_for_schema()` to add the tag to existing templates

Example PHP code to update existing calendars:
```php
$updated_count = simcal_update_calendar_templates_for_schema();
echo "Updated {$updated_count} calendars with schema-meta tag.";
```

## Testing

After implementing these changes:

1. **Check HTML source**: View your calendar events and verify the schema properties are present in the HTML
2. **Google Rich Results Test**: Use https://search.google.com/test/rich-results to validate the structured data
3. **Monitor Search Console**: Check Google Search Console for reduced structured data errors

## Example Output

The schema properties are added as hidden meta tags within the event HTML:

```html
<li class="simcal-event" itemscope itemtype="http://schema.org/Event">
    <!-- Event content -->
    <meta itemprop="eventAttendanceMode" content="https://schema.org/OfflineEventAttendanceMode" />
    <meta itemprop="eventStatus" content="https://schema.org/EventScheduled" />
    <div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
        <meta itemprop="price" content="0" />
        <meta itemprop="priceCurrency" content="USD" />
        <meta itemprop="availability" content="https://schema.org/InStock" />
    </div>
    <meta itemprop="image" content="https://example.com/logo.png" />
    <div itemprop="performer" itemscope itemtype="https://schema.org/Person">
        <meta itemprop="name" content="Event Organizer" />
    </div>
    <div itemprop="organizer" itemscope itemtype="https://schema.org/Organization">
        <meta itemprop="name" content="Site Name" />
        <meta itemprop="url" content="https://example.com" />
    </div>
    <meta itemprop="description" content="Event Title" />
</li>
```

## Customization

The schema properties can be customized by:

1. **Filtering the default template**: Use the `simcal_default_event_template` filter
2. **Modifying the schema properties**: Override the `get_missing_schema_properties()` method in a child class
3. **Custom event templates**: Add `[schema-meta]` to your custom event templates for explicit control

## Notes

- The schema properties are added as hidden meta tags and won't affect the visual appearance of events
- The implementation follows Google's structured data guidelines for Event schema
- All properties use sensible defaults that work for most calendar use cases
- **No template updates required** - existing calendars automatically get schema properties
- The solution is backward-compatible and won't break existing functionality