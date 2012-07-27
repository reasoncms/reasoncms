<?php
include_once('paths.php');
include_once(DISCO_INC.'plasmature/plasmature.php');
require_once PLASMATURE_TYPES_INC."options.php";

/**
 * Type library for offering athletic positions or events for Luther College sports
 * @package disco
 * @subpackage plasmature
 */

class baseball_softball_positionsType extends selectType
{
 	var $type = 'baseball_softball_positions';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'P' => 'Pitcher',
			'C' => 'Catcher',
			'IF' => 'Infield',
			'1B' => 'First Base',
			'2B' => 'Second Base',
			'3B' => 'Third Base',
			'SS' => 'Shortstop',
			'OF' => 'Outfield',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class basketball_positionsType extends selectType
{
 	var $type = 'basketball_positions';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'C' => 'Center',
			'F' => 'Forward',
			'G' => 'Guard',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class football_positionsType extends selectType
{
 	var $type = 'football_positions';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'DB' => 'Defensive Back',
			'DL' => 'Defensive Line',
			'FB' => 'Fullback',
			'K' => 'Kicker',
			'LB' => 'Linebacker',
			'OL' => 'Offensive Line',
			'QB' => 'Quarterback',
			'RB' => 'Running Back',
			'TE' => 'Tight End',
			'WR' => 'Wide Receiver',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class soccer_positionsType extends selectType
{
 	var $type = 'soccer_positions';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'GK' => 'Goalkeeper',
			'D' => 'Defender',
			'MF' => 'Midfielder',
			'F' => 'Forward',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class volleyball_positionsType extends selectType
{
 	var $type = 'volleyball_positions';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'DS' => 'Defensive Specialist',
			'L' => 'Libero',
			'MB' => 'Middle Blocker',
			'OH' => 'Outside Hitter',
			'R' => 'Right Side Hitter',
			'S' => 'Setter',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class swimming_eventsType extends selectType
{
 	var $type = 'swimming_events';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'BU' => 'Butterfly',
			'BA' => 'Backstroke',
			'BR' => 'Breaststroke',
			'FR' => 'Freestyle',
			'IM' => 'Individual Medley',
			'D' => 'Diving',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}

class track_eventsType extends selectType
{
 	var $type = 'track_events';
	var $sort_options = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$positions = array(
			'D' => 'Distance',
			'H' => 'Hurdles',
			'J' => 'Jumps',
			'MD' => 'Mid-distance',
			'ME' => 'Multi-events',
			'PV' => 'Pole Vault',
			'S' => 'Sprints',
			'T' => 'Throws',
		);
		foreach( $positions as $key => $val )
			$this->options[ $key ] = $val;
	}
}
?>
