<?php

interface dateRangeFormatter
{
	public static function format($start_date, $end_date = NULL, $recurrence_info = array());
}