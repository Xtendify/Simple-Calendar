<?php

public function test_simple_calendar_shortcode() {
  // Arrange
  $shortcode_atts = array(
    'start_date' => '2023-03-01',
    'end_date' => '2023-03-31',
  );

  // Act
  $calendar_html = simple_calendar_shortcode($shortcode_atts);

  // Assert
  $this->assertStringContainsString('March 2023', $calendar_html);
  $this->assertStringContainsString('<td>1</td>', $calendar_html);
  $this->assertStringContainsString('<td>31</td>', $calendar_html);
}
