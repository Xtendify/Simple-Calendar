<?php

namespace SimpleCalendar\Tests\Unit\Events;

use SimpleCalendar\Events\Event;
use SimpleCalendar\Tests\Unit\TestCase;
use SimpleCalendar\plugin_deps\Carbon\Carbon;

class EventTest extends TestCase
{
	private function makeEvent(array $overrides = []): Event
	{
		$defaults = [
			'uid' => 'test-uid-123',
			'ical_id' => 'ical-456',
			'source' => 'google',
			'calendar' => 42,
			'timezone' => 'America/New_York',
			'title' => 'Team Standup',
			'description' => '<p>Daily standup meeting</p>',
			'link' => 'https://example.com/event',
			'visibility' => 'public',
			'start' => Carbon::now('UTC')->getTimestamp(),
			'start_utc' => Carbon::now('UTC')->getTimestamp(),
			'start_timezone' => 'America/New_York',
			'end' => Carbon::now('UTC')->addHour()->getTimestamp(),
			'end_utc' => Carbon::now('UTC')->addHour()->getTimestamp(),
			'end_timezone' => 'America/New_York',
			'start_location' => ['name' => 'Office', 'address' => '123 Main St', 'lat' => 40.7128, 'lng' => -74.006],
			'end_location' => '',
			'whole_day' => false,
			'multiple_days' => false,
			'recurrence' => false,
			'meta' => [
				'color' => '#ff0000',
				'attendees' => [['name' => 'Alice']],
				'organizer' => ['name' => 'Bob'],
				'attachments' => [['url' => 'file.pdf']],
			],
			'template' => '<strong>[title]</strong>',
		];

		return new Event(array_merge($defaults, $overrides));
	}

	public function testConstructWithMinimalData()
	{
		$event = new Event([
			'start' => 1700000000,
			'start_timezone' => 'UTC',
			'start_location' => '',
			'end' => 1700003600,
			'end_timezone' => 'UTC',
			'end_location' => '',
		]);
		$this->assertSame('', $event->uid);
		$this->assertSame('', $event->title);
		$this->assertSame('', $event->description);
		$this->assertSame(1700000000, $event->start);
		$this->assertSame(1700003600, $event->end);
		$this->assertFalse($event->whole_day);
		$this->assertFalse($event->multiple_days);
		$this->assertFalse($event->recurrence);
		$this->assertSame([], $event->meta);
	}

	public function testIdentifiersAreSet()
	{
		$event = $this->makeEvent();
		$this->assertSame('test-uid-123', $event->uid);
		$this->assertSame('ical-456', $event->ical_id);
		$this->assertSame('google', $event->source);
		$this->assertSame(42, $event->calendar);
		$this->assertSame('America/New_York', $event->timezone);
	}

	public function testContentIsSet()
	{
		$event = $this->makeEvent();
		$this->assertSame('Team Standup', $event->title);
		$this->assertSame('<p>Daily standup meeting</p>', $event->description);
		$this->assertSame('https://example.com/event', $event->link);
	}

	public function testVisibilityPublic()
	{
		$event = $this->makeEvent(['visibility' => 'public']);
		$this->assertSame('public', $event->visibility);
		$this->assertTrue($event->public);
	}

	public function testVisibilityPrivate()
	{
		$event = $this->makeEvent(['visibility' => 'private']);
		$this->assertSame('private', $event->visibility);
		$this->assertFalse($event->public);
	}

	public function testStartTimestamp()
	{
		$ts = 1700000000;
		$event = $this->makeEvent(['start' => $ts, 'start_utc' => $ts, 'start_timezone' => 'UTC']);
		$this->assertSame($ts, $event->start);
		$this->assertSame($ts, $event->start_utc);
		$this->assertSame('UTC', $event->start_timezone);
		$this->assertInstanceOf(Carbon::class, $event->start_dt);
	}

	public function testEndTimestamp()
	{
		$ts = 1700003600;
		$event = $this->makeEvent(['end' => $ts, 'end_utc' => $ts, 'end_timezone' => 'Europe/London']);
		$this->assertSame($ts, $event->end);
		$this->assertSame($ts, $event->end_utc);
		$this->assertSame('Europe/London', $event->end_timezone);
		$this->assertInstanceOf(Carbon::class, $event->end_dt);
	}

	public function testNoEndTimestamp()
	{
		$event = $this->makeEvent(['end' => null, 'end_utc' => null]);
		$this->assertFalse($event->end);
		$this->assertNull($event->end_dt);
	}

	public function testStartLocationFromArray()
	{
		$event = $this->makeEvent([
			'start_location' => ['name' => 'Office', 'address' => '123 Main St', 'lat' => 40.7128, 'lng' => -74.006],
		]);
		$this->assertSame('Office', $event->start_location['name']);
		$this->assertSame('123 Main St', $event->start_location['address']);
		$this->assertSame(40.7128, $event->start_location['lat']);
		$this->assertTrue($event->start_location['venue']);
	}

	public function testStartLocationFromString()
	{
		$event = $this->makeEvent(['start_location' => 'Conference Room A']);
		$this->assertSame('Conference Room A', $event->start_location['name']);
		$this->assertSame('Conference Room A', $event->start_location['address']);
		$this->assertTrue($event->start_location['venue']);
	}

	public function testEmptyLocationSetsVenueFalse()
	{
		$event = $this->makeEvent(['start_location' => '', 'end_location' => '']);
		$this->assertFalse($event->start_location['venue']);
		$this->assertFalse($event->venue);
	}

	public function testNullLocationHandled()
	{
		$event = $this->makeEvent(['start_location' => null, 'end_location' => null]);
		$this->assertFalse($event->start_location['venue']);
	}

	public function testWholeDay()
	{
		$event = $this->makeEvent(['whole_day' => true]);
		$this->assertTrue($event->whole_day);
	}

	public function testMultipleDays()
	{
		$event = $this->makeEvent(['multiple_days' => 3]);
		$this->assertSame(3, $event->multiple_days);
	}

	public function testRecurrence()
	{
		$recurrence = ['frequency' => 'weekly', 'interval' => 1];
		$event = $this->makeEvent(['recurrence' => $recurrence]);
		$this->assertSame($recurrence, $event->recurrence);
	}

	public function testEmptyRecurrenceSetsFalse()
	{
		$event = $this->makeEvent(['recurrence' => '']);
		$this->assertFalse($event->recurrence);
	}

	public function testVenueIsTrueWhenStartHasLocation()
	{
		$event = $this->makeEvent([
			'start_location' => 'Some Place',
			'end_location' => '',
		]);
		$this->assertTrue($event->venue);
	}

	public function testMetaIsSet()
	{
		$meta = ['color' => '#00ff00', 'custom' => 'value'];
		$event = $this->makeEvent(['meta' => $meta]);
		$this->assertSame($meta, $event->meta);
	}

	public function testNonArrayMetaBecomesEmpty()
	{
		$event = $this->makeEvent(['meta' => 'not-an-array']);
		$this->assertSame([], $event->meta);
	}

	public function testTemplateIsSet()
	{
		$event = $this->makeEvent(['template' => '<strong>[title]</strong>']);
		$this->assertSame('<strong>[title]</strong>', $event->template);
	}

	public function testSetTimezoneWithValidTimezone()
	{
		$event = $this->makeEvent();
		$this->assertTrue($event->set_timezone('Europe/Paris'));
		$this->assertSame('Europe/Paris', $event->timezone);
	}

	public function testSetTimezoneWithInvalidTimezone()
	{
		$event = $this->makeEvent();
		$original = $event->timezone;
		$this->assertFalse($event->set_timezone('Invalid/Zone'));
		$this->assertSame($original, $event->timezone);
	}

	public function testGetColorFromMeta()
	{
		$event = $this->makeEvent(['meta' => ['color' => '#ff0000']]);
		$this->assertSame('#ff0000', $event->get_color());
	}

	public function testGetColorDefaultWhenMissing()
	{
		$event = $this->makeEvent(['meta' => []]);
		$this->assertSame('#333', $event->get_color('#333'));
	}

	public function testGetColorDefaultWhenEmpty()
	{
		$event = $this->makeEvent(['meta' => ['color' => '']]);
		$this->assertSame('#333', $event->get_color('#333'));
	}

	public function testGetAttachments()
	{
		$attachments = [['url' => 'file.pdf']];
		$event = $this->makeEvent(['meta' => ['attachments' => $attachments]]);
		$this->assertSame($attachments, $event->get_attachments());
	}

	public function testGetAttachmentsEmpty()
	{
		$event = $this->makeEvent(['meta' => []]);
		$this->assertSame([], $event->get_attachments());
	}

	public function testGetAttendees()
	{
		$attendees = [['name' => 'Alice'], ['name' => 'Bob']];
		$event = $this->makeEvent(['meta' => ['attendees' => $attendees]]);
		$this->assertSame($attendees, $event->get_attendees());
	}

	public function testGetAttendeesEmpty()
	{
		$event = $this->makeEvent(['meta' => []]);
		$this->assertSame([], $event->get_attendees());
	}

	public function testGetOrganizer()
	{
		$organizer = ['name' => 'Bob', 'email' => 'bob@example.com'];
		$event = $this->makeEvent(['meta' => ['organizer' => $organizer]]);
		$this->assertSame($organizer, $event->get_organizer());
	}

	public function testGetOrganizerEmpty()
	{
		$event = $this->makeEvent(['meta' => []]);
		$this->assertSame([], $event->get_organizer());
	}

	public function testNonNumericStartIsZero()
	{
		$event = $this->makeEvent(['start' => 'not-a-number']);
		$this->assertSame(0, $event->start);
	}

	public function testNegativeCalendarIdIsZero()
	{
		$event = $this->makeEvent(['calendar' => -5]);
		$this->assertSame(0, $event->calendar);
	}

	public function testCoordinateEscaping()
	{
		$event = $this->makeEvent([
			'start_location' => ['name' => 'Place', 'address' => 'Addr', 'lat' => 'invalid', 'lng' => 91.5],
		]);
		$this->assertEquals(0, $event->start_location['lat']);
		$this->assertSame(91.5, $event->start_location['lng']);
	}
}
