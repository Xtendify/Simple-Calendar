<?php

namespace SimpleCalendar\Tests\Unit;

use SimpleCalendar\Objects;
use Brain\Monkey\Functions;

class ObjectsTest extends TestCase
{
	private \ReflectionMethod $makeClassName;
	private Objects $objects;

	protected function setUp(): void
	{
		parent::setUp();

		Functions\stubs([
			'add_filter' => null,
			'is_admin' => false,
			'do_action' => null,
		]);

		$this->objects = new Objects();

		$this->makeClassName = new \ReflectionMethod(Objects::class, 'make_class_name');
		$this->makeClassName->setAccessible(true);
	}

	public function testCalendarType()
	{
		$result = $this->makeClassName->invoke($this->objects, 'default-calendar', 'calendar');
		$this->assertSame('\SimpleCalendar\Calendars\Default_Calendar', $result);
	}

	public function testCalendarViewType()
	{
		$result = $this->makeClassName->invoke($this->objects, 'default-calendar-grid', 'calendar-view');
		$this->assertSame('\SimpleCalendar\Calendars\Views\Default_Calendar_Grid', $result);
	}

	public function testFeedType()
	{
		$result = $this->makeClassName->invoke($this->objects, 'google', 'feed');
		$this->assertSame('\SimpleCalendar\Feeds\Google', $result);
	}

	public function testFieldType()
	{
		$result = $this->makeClassName->invoke($this->objects, 'standard', 'field');
		$this->assertSame('\SimpleCalendar\Admin\Fields\Standard', $result);
	}

	public function testAdminPageType()
	{
		$result = $this->makeClassName->invoke($this->objects, 'add-ons', 'admin-page');
		$this->assertSame('\SimpleCalendar\Admin\Pages\Add_Ons', $result);
	}

	public function testInvalidTypeReturnsEmpty()
	{
		$result = $this->makeClassName->invoke($this->objects, 'something', 'invalid-type');
		$this->assertSame('', $result);
	}

	public function testGroupedCalendarsFeed()
	{
		$result = $this->makeClassName->invoke($this->objects, 'grouped-calendars', 'feed');
		$this->assertSame('\SimpleCalendar\Feeds\Grouped_Calendars', $result);
	}

	public function testListView()
	{
		$result = $this->makeClassName->invoke($this->objects, 'default-calendar-list', 'calendar-view');
		$this->assertSame('\SimpleCalendar\Calendars\Views\Default_Calendar_List', $result);
	}
}
