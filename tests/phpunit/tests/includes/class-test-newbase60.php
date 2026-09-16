<?php
/**
 * Test file for includes/newbase60.php.
 *
 * @package Hum
 */

namespace Hum\Tests\Includes;

/**
 * Test class for the NewBase60 helpers.
 *
 * See http://ttk.me/w/NewBase60 for the alphabet and the typo corrections.
 */
class Test_Newbase60 extends \WP_UnitTestCase {

	/**
	 * Known number / sexagesimal pairs.
	 *
	 * @return array
	 */
	public function data_pairs() {
		return array(
			'single digit'    => array( 1, '1' ),
			'last digit'      => array( 9, '9' ),
			'first letter'    => array( 10, 'A' ),
			'skips I'         => array( 18, 'J' ),
			'skips O'         => array( 23, 'P' ),
			'underscore'      => array( 34, '_' ),
			'first lowercase' => array( 35, 'a' ),
			'skips l'         => array( 46, 'm' ),
			'highest digit'   => array( 59, 'z' ),
			'two digits'      => array( 60, '10' ),
			'readme example'  => array( 918, 'FJ' ),
			'three digits'    => array( 3600, '100' ),
			'large number'    => array( 2147483647, '2kh3E7' ),
		);
	}

	/**
	 * Numbers encode to the expected sexagesimal string.
	 *
	 * @dataProvider data_pairs
	 * @covers ::num_to_sxg
	 *
	 * @param int    $num Number.
	 * @param string $sxg Sexagesimal.
	 */
	public function test_num_to_sxg( $num, $sxg ) {
		$this->assertSame( $sxg, \num_to_sxg( $num ) );
	}

	/**
	 * Sexagesimal strings decode to the expected number.
	 *
	 * @dataProvider data_pairs
	 * @covers ::sxg_to_num
	 *
	 * @param int    $num Number.
	 * @param string $sxg Sexagesimal.
	 */
	public function test_sxg_to_num( $num, $sxg ) {
		$this->assertSame( $num, \sxg_to_num( $sxg ) );
	}

	/**
	 * Zero and null encode to zero.
	 *
	 * @covers ::num_to_sxg
	 */
	public function test_zero_and_null_encode_to_zero() {
		$this->assertEquals( '0', \num_to_sxg( 0 ) );
		$this->assertEquals( '0', \num_to_sxg( null ) );
	}

	/**
	 * Every character of the alphabet round-trips.
	 *
	 * @covers ::num_to_sxg
	 * @covers ::sxg_to_num
	 */
	public function test_alphabet_round_trips() {
		$alphabet = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ_abcdefghijkmnopqrstuvwxyz';

		$this->assertSame( 60, \strlen( $alphabet ) );

		for ( $i = 1; $i < 60; $i++ ) {
			$this->assertSame( $alphabet[ $i ], \num_to_sxg( $i ) );
			$this->assertSame( $i, \sxg_to_num( $alphabet[ $i ] ) );
		}
	}

	/**
	 * Random numbers survive an encode / decode round-trip.
	 *
	 * @covers ::num_to_sxg
	 * @covers ::sxg_to_num
	 */
	public function test_random_numbers_round_trip() {
		foreach ( array( 7, 61, 3599, 12345, 999999, 60 ** 4, 1234567890 ) as $num ) {
			$this->assertSame( $num, \sxg_to_num( \num_to_sxg( $num ) ) );
		}
	}

	/**
	 * Commonly confused characters are corrected while decoding.
	 *
	 * @covers ::sxg_to_num
	 */
	public function test_typo_correction() {
		$this->assertSame( \sxg_to_num( '1' ), \sxg_to_num( 'I' ) );
		$this->assertSame( \sxg_to_num( '1' ), \sxg_to_num( 'l' ) );
		$this->assertSame( \sxg_to_num( '0' ), \sxg_to_num( 'O' ) );
		$this->assertSame( \sxg_to_num( 'F1' ), \sxg_to_num( 'FI' ) );
		$this->assertSame( \sxg_to_num( 'F1' ), \sxg_to_num( 'Fl' ) );
		$this->assertSame( \sxg_to_num( 'F0' ), \sxg_to_num( 'FO' ) );
	}

	/**
	 * Characters outside the alphabet count as zero.
	 *
	 * @covers ::sxg_to_num
	 */
	public function test_noise_counts_as_zero() {
		$this->assertSame( 0, \sxg_to_num( '' ) );
		$this->assertSame( 0, \sxg_to_num( '-' ) );
		$this->assertSame( 0, \sxg_to_num( '!?' ) );
		$this->assertSame( \sxg_to_num( 'F0' ), \sxg_to_num( 'F-' ) );
		$this->assertSame( \sxg_to_num( 'F0' ), \sxg_to_num( 'F ' ) );
	}
}
