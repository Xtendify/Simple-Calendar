<?php

namespace SimpleCalendar\Tests\Unit\Events;

use Brain\Monkey\Functions;
use Mockery;
use SimpleCalendar\Events\Event;
use SimpleCalendar\Events\Event_Builder;
use SimpleCalendar\Tests\Unit\TestCase;

class EventBuilderLimitWordsTest extends TestCase
{
	private \ReflectionMethod $limitWords;
	private Event_Builder $builder;

	protected function setUp(): void
	{
		parent::setUp();

		Functions\stubs([
			'get_option' => '',
		]);

		$event = Mockery::mock(Event::class)->makePartial();
		$calendar = Mockery::mock('SimpleCalendar\Abstracts\Calendar')->makePartial();

		$this->builder = new Event_Builder($event, $calendar);
		$this->limitWords = new \ReflectionMethod(Event_Builder::class, 'limit_words');
		$this->limitWords->setAccessible(true);
	}

	public function testTruncatesExceedingWords()
	{
		$result = $this->limitWords->invoke($this->builder, 'one two three', 2);
		$this->assertSame('one two&hellip;', $result);
	}

	public function testDoesNotTruncateWhenUnderLimit()
	{
		$result = $this->limitWords->invoke($this->builder, 'hello', 5);
		$this->assertSame('hello', $result);
	}

	public function testDoesNotAddEllipsisAtExactLimit()
	{
		$result = $this->limitWords->invoke($this->builder, 'one two three', 3);
		$this->assertSame('one two three', $result);
	}

	public function testEmptyStringReturnsEmpty()
	{
		$result = $this->limitWords->invoke($this->builder, '', 5);
		$this->assertSame('', $result);
	}

	public function testZeroLimitReturnsOriginal()
	{
		$result = $this->limitWords->invoke($this->builder, 'one two three', 0);
		$this->assertSame('one two three', $result);
	}
}
