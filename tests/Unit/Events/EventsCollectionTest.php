<?php

namespace SimpleCalendar\Tests\Unit\Events;

use SimpleCalendar\Events\Events;
use SimpleCalendar\Tests\Unit\TestCase;

class EventsCollectionTest extends TestCase
{
    private function makeEvent(array $props = []): object
    {
        return (object) array_merge([
            'public'        => true,
            'recurrence'    => false,
            'whole_day'     => false,
            'multiple_days' => false,
            'venue'         => false,
        ], $props);
    }

    private function makeEvents(array $rows): array
    {
        $events = [];
        foreach ($rows as $ts => $propsArray) {
            foreach ($propsArray as $props) {
                $events[$ts][] = $this->makeEvent($props);
            }
        }
        return $events;
    }

    public function testEmptyConstructor()
    {
        $collection = new Events();
        $this->assertSame([], $collection->get_events());
    }

    public function testConstructorStoresEvents()
    {
        $raw = $this->makeEvents([1000 => [[]], 2000 => [[]]]);
        $collection = new Events($raw);
        $this->assertSame($raw, $collection->get_events());
    }

    public function testGetEventsWithLimit()
    {
        $raw = $this->makeEvents([1000 => [[]], 2000 => [[]], 3000 => [[]]]);
        $collection = new Events($raw);
        $this->assertCount(2, $collection->get_events(2));
    }

    public function testGetEventsWithZeroReturnsAll()
    {
        $raw = $this->makeEvents([1000 => [[]], 2000 => [[]], 3000 => [[]]]);
        $collection = new Events($raw);
        $this->assertCount(3, $collection->get_events(0));
    }

    public function testShiftRemovesFirstEntry()
    {
        $raw = $this->makeEvents([1000 => [[]], 2000 => [[]], 3000 => [[]]]);
        $collection = new Events($raw);
        $result = $collection->shift(1);

        $this->assertSame($collection, $result);
        $this->assertCount(2, $collection->get_events());
        $this->assertArrayNotHasKey(1000, $collection->get_events());
    }

    public function testShiftOnEmptyCollectionIsNoop()
    {
        $collection = new Events();
        $collection->shift(1);
        $this->assertSame([], $collection->get_events());
    }

    public function testPublicOnly()
    {
        $raw = $this->makeEvents([
            1000 => [['public' => true]],
            2000 => [['public' => false]],
            3000 => [['public' => true]],
        ]);
        $collection = new Events($raw);
        $collection->public_only();
        $events = $collection->get_events();

        $this->assertCount(2, $events);
        foreach ($events as $ts => $group) {
            foreach ($group as $event) {
                $this->assertTrue($event->public);
            }
        }
    }

    public function testPrivateOnly()
    {
        $raw = $this->makeEvents([
            1000 => [['public' => true]],
            2000 => [['public' => false]],
        ]);
        $collection = new Events($raw);
        $collection->private_only();
        $events = $collection->get_events();

        $this->assertCount(1, $events);
        $this->assertArrayHasKey(2000, $events);
    }

    public function testRecurring()
    {
        $raw = $this->makeEvents([
            1000 => [['recurrence' => ['freq' => 'weekly']]],
            2000 => [['recurrence' => false]],
        ]);
        $collection = new Events($raw);
        $collection->recurring();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(1000, $collection->get_events());
    }

    public function testNotRecurring()
    {
        $raw = $this->makeEvents([
            1000 => [['recurrence' => ['freq' => 'weekly']]],
            2000 => [['recurrence' => false]],
        ]);
        $collection = new Events($raw);
        $collection->not_recurring();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(2000, $collection->get_events());
    }

    public function testWholeDay()
    {
        $raw = $this->makeEvents([
            1000 => [['whole_day' => true]],
            2000 => [['whole_day' => false]],
        ]);
        $collection = new Events($raw);
        $collection->whole_day();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(1000, $collection->get_events());
    }

    public function testNotWholeDay()
    {
        $raw = $this->makeEvents([
            1000 => [['whole_day' => true]],
            2000 => [['whole_day' => false]],
        ]);
        $collection = new Events($raw);
        $collection->not_whole_day();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(2000, $collection->get_events());
    }

    public function testMultiDay()
    {
        $raw = $this->makeEvents([
            1000 => [['multiple_days' => 3]],
            2000 => [['multiple_days' => false]],
        ]);
        $collection = new Events($raw);
        $collection->multi_day();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(1000, $collection->get_events());
    }

    public function testSingleDay()
    {
        $raw = $this->makeEvents([
            1000 => [['multiple_days' => 3]],
            2000 => [['multiple_days' => false]],
        ]);
        $collection = new Events($raw);
        $collection->single_day();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(2000, $collection->get_events());
    }

    public function testWithLocation()
    {
        $raw = $this->makeEvents([
            1000 => [['venue' => true]],
            2000 => [['venue' => false]],
        ]);
        $collection = new Events($raw);
        $collection->with_location();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(1000, $collection->get_events());
    }

    public function testWithoutLocation()
    {
        $raw = $this->makeEvents([
            1000 => [['venue' => true]],
            2000 => [['venue' => false]],
        ]);
        $collection = new Events($raw);
        $collection->without_location();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(2000, $collection->get_events());
    }

    public function testFiltersChain()
    {
        $raw = $this->makeEvents([
            1000 => [['public' => true,  'whole_day' => true]],
            2000 => [['public' => true,  'whole_day' => false]],
            3000 => [['public' => false, 'whole_day' => true]],
        ]);
        $collection = new Events($raw);
        $collection->public_only()->whole_day();

        $this->assertCount(1, $collection->get_events());
        $this->assertArrayHasKey(1000, $collection->get_events());
    }

    public function testFilterOnEmptyCollectionReturnsEmpty()
    {
        $collection = new Events();
        $collection->public_only();
        $this->assertSame([], $collection->get_events());
    }

    public function testSetTimezoneWithString()
    {
        $collection = new Events();
        $collection->set_timezone('America/New_York');
        $this->assertInstanceOf(Events::class, $collection->set_timezone('UTC'));
    }

    public function testSetTimezoneWithDateTimeZoneObject()
    {
        $collection = new Events();
        $result = $collection->set_timezone(new \DateTimeZone('Europe/London'));
        $this->assertInstanceOf(Events::class, $result);
    }

    public function testMultipleEventsPerTimestampFiltered()
    {
        $raw = $this->makeEvents([
            1000 => [
                ['public' => true],
                ['public' => false],
                ['public' => true],
            ],
        ]);
        $collection = new Events($raw);
        $collection->public_only();

        $events = $collection->get_events();
        $this->assertCount(1, $events);
        $this->assertCount(2, $events[1000]);
    }
}
