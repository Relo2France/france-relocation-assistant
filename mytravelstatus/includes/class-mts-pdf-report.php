<?php
/**
 * PDF Report Generator for MyTravelStatus.
 *
 * Generates professional, audit-ready PDF compliance reports.
 *
 * @package MTS_Tracker
 * @since   1.8.2
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDF Report generation class.
 */
class MTS_PDF_Report {

	/**
	 * User ID for the report.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Report period start date.
	 *
	 * @var DateTime
	 */
	private $period_start;

	/**
	 * Report period end date.
	 *
	 * @var DateTime
	 */
	private $period_end;

	/**
	 * Jurisdiction handler instance.
	 *
	 * @var MTS_Jurisdiction
	 */
	private $jurisdiction;

	/**
	 * Generated report ID.
	 *
	 * @var string
	 */
	private $report_id;

	/**
	 * Brand colors.
	 *
	 * @var array
	 */
	private $colors = array(
		'primary'   => array( 74, 123, 167 ),  // #4A7BA7 - Blue
		'secondary' => array( 229, 165, 75 ),  // #E5A54B - Gold
		'success'   => array( 34, 197, 94 ),   // Green
		'warning'   => array( 234, 179, 8 ),   // Yellow
		'danger'    => array( 239, 68, 68 ),   // Red
		'text'      => array( 45, 55, 72 ),    // Dark gray
		'light'     => array( 113, 128, 150 ), // Light gray
	);

	/**
	 * Constructor.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $period_start Period start date (Y-m-d).
	 * @param string $period_end   Period end date (Y-m-d).
	 */
	public function __construct( $user_id, $period_start, $period_end ) {
		$this->user_id      = $user_id;
		$this->period_start = new DateTime( $period_start );
		$this->period_end   = new DateTime( $period_end );
		$this->report_id    = $this->generate_report_id();
		$this->jurisdiction = new MTS_Jurisdiction();
	}

	/**
	 * Generate unique report ID.
	 *
	 * @return string Report ID.
	 */
	private function generate_report_id() {
		$year   = gmdate( 'Y' );
		$random = strtoupper( substr( wp_generate_password( 6, false ), 0, 6 ) );
		return "MTS-{$year}-{$this->user_id}-{$random}";
	}

	/**
	 * Generate the PDF report.
	 *
	 * @param array $options Report options (jurisdictions, include_trips, etc.).
	 * @return array|WP_Error Report data with file path or error.
	 */
	public function generate( $options = array() ) {
		// Check if TCPDF is available.
		if ( ! class_exists( 'TCPDF' ) ) {
			// Try to load composer autoloader.
			$autoloader = MTS_PLUGIN_DIR . 'vendor/autoload.php';
			if ( file_exists( $autoloader ) ) {
				require_once $autoloader;
			}
		}

		if ( ! class_exists( 'TCPDF' ) ) {
			return new WP_Error(
				'pdf_library_missing',
				'PDF library not available. Please run composer install.',
				array( 'status' => 500 )
			);
		}

		$defaults = array(
			'jurisdictions'   => array(), // Empty = all enabled.
			'include_trips'   => true,
			'include_qr'      => true,
			'title'           => 'Travel Compliance Report',
		);
		$options  = wp_parse_args( $options, $defaults );

		try {
			// Create PDF document.
			$pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );

			// Set document information.
			$pdf->SetCreator( 'MyTravelStatus' );
			$pdf->SetAuthor( 'MyTravelStatus' );
			$pdf->SetTitle( $options['title'] );
			$pdf->SetSubject( 'Travel Compliance Report' );

			// Remove default header/footer.
			$pdf->setPrintHeader( false );
			$pdf->setPrintFooter( false );

			// Set margins.
			$pdf->SetMargins( 15, 15, 15 );
			$pdf->SetAutoPageBreak( true, 20 );

			// Set font.
			$pdf->SetFont( 'helvetica', '', 10 );

			// Add first page.
			$pdf->AddPage();

			// Generate report content.
			$this->add_report_header( $pdf, $options );
			$this->add_compliance_summary( $pdf, $options );
			$this->add_jurisdiction_details( $pdf, $options );

			if ( $options['include_trips'] ) {
				$this->add_trip_log( $pdf, $options );
			}

			$this->add_methodology( $pdf );
			$this->add_disclaimer( $pdf );

			if ( $options['include_qr'] ) {
				$this->add_verification( $pdf );
			}

			// Generate output path.
			$upload_dir = wp_upload_dir();
			$report_dir = $upload_dir['basedir'] . '/mts-reports/' . $this->user_id;

			if ( ! file_exists( $report_dir ) ) {
				wp_mkdir_p( $report_dir );
			}

			$filename  = sanitize_file_name( $this->report_id . '.pdf' );
			$file_path = $report_dir . '/' . $filename;
			$file_url  = $upload_dir['baseurl'] . '/mts-reports/' . $this->user_id . '/' . $filename;

			// Save PDF.
			$pdf->Output( $file_path, 'F' );

			// Generate verification hash.
			$hash = hash_file( 'sha256', $file_path );

			// Store report metadata.
			$this->store_report_metadata( $file_path, $hash, $options );

			return array(
				'report_id'  => $this->report_id,
				'file_path'  => $file_path,
				'file_url'   => $file_url,
				'hash'       => $hash,
				'generated'  => gmdate( 'Y-m-d H:i:s' ),
				'period'     => array(
					'start' => $this->period_start->format( 'Y-m-d' ),
					'end'   => $this->period_end->format( 'Y-m-d' ),
				),
			);

		} catch ( Exception $e ) {
			return new WP_Error(
				'pdf_generation_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Add report header section.
	 *
	 * @param TCPDF $pdf     PDF instance.
	 * @param array $options Report options.
	 */
	private function add_report_header( $pdf, $options ) {
		// Title.
		$pdf->SetFont( 'helvetica', 'B', 20 );
		$pdf->SetTextColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->Cell( 0, 10, 'TRAVEL COMPLIANCE REPORT', 0, 1, 'C' );

		$pdf->SetFont( 'helvetica', '', 12 );
		$pdf->SetTextColor( $this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2] );
		$pdf->Cell( 0, 6, 'MyTravelStatus', 0, 1, 'C' );

		$pdf->Ln( 5 );

		// Horizontal line.
		$pdf->SetDrawColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->SetLineWidth( 0.5 );
		$pdf->Line( 15, $pdf->GetY(), 195, $pdf->GetY() );
		$pdf->Ln( 5 );

		// Report details.
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->Cell( 0, 6, 'REPORT DETAILS', 0, 1, 'L' );

		$pdf->SetFont( 'helvetica', '', 10 );
		$user = get_userdata( $this->user_id );

		$details = array(
			'Report ID'     => $this->report_id,
			'Generated'     => gmdate( 'F j, Y \a\t H:i \U\T\C' ),
			'Report Period' => $this->period_start->format( 'F j, Y' ) . ' - ' . $this->period_end->format( 'F j, Y' ),
			'User'          => $user ? $user->display_name . ' (ID: ' . $this->user_id . ')' : 'User ID: ' . $this->user_id,
			'Email'         => $user ? $user->user_email : 'N/A',
		);

		foreach ( $details as $label => $value ) {
			$pdf->SetFont( 'helvetica', '', 10 );
			$pdf->Cell( 40, 5, $label . ':', 0, 0, 'L' );
			$pdf->SetFont( 'helvetica', '', 10 );
			$pdf->Cell( 0, 5, $value, 0, 1, 'L' );
		}

		$pdf->Ln( 8 );
	}

	/**
	 * Add compliance summary section.
	 *
	 * @param TCPDF $pdf     PDF instance.
	 * @param array $options Report options.
	 */
	private function add_compliance_summary( $pdf, $options ) {
		// Section title.
		$pdf->SetFillColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->Cell( 0, 8, ' COMPLIANCE SUMMARY', 1, 1, 'L', true );

		$pdf->Ln( 2 );

		// Get enabled jurisdictions.
		$jurisdictions = $this->get_user_jurisdictions( $options['jurisdictions'] );
		$trips         = $this->get_user_trips();

		if ( empty( $jurisdictions ) ) {
			$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
			$pdf->SetFont( 'helvetica', 'I', 10 );
			$pdf->Cell( 0, 6, 'No jurisdictions enabled for tracking.', 0, 1, 'L' );
			$pdf->Ln( 5 );
			return;
		}

		// Table header.
		$pdf->SetFillColor( 245, 247, 250 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->SetFont( 'helvetica', 'B', 9 );

		$pdf->Cell( 55, 7, 'Jurisdiction', 1, 0, 'L', true );
		$pdf->Cell( 25, 7, 'Status', 1, 0, 'C', true );
		$pdf->Cell( 30, 7, 'Days Used', 1, 0, 'C', true );
		$pdf->Cell( 25, 7, 'Limit', 1, 0, 'C', true );
		$pdf->Cell( 30, 7, 'Margin', 1, 1, 'C', true );

		// Table rows.
		$pdf->SetFont( 'helvetica', '', 9 );

		foreach ( $jurisdictions as $j ) {
			$summary = $this->jurisdiction->calculate_summary(
				$trips,
				$j,
				$this->period_end
			);

			$status_text  = $this->get_status_text( $summary['status'] );
			$status_color = $this->get_status_color( $summary['status'] );

			$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
			$pdf->Cell( 55, 6, $j['flagEmoji'] . ' ' . $j['name'], 1, 0, 'L' );

			$pdf->SetTextColor( $status_color[0], $status_color[1], $status_color[2] );
			$pdf->Cell( 25, 6, $status_text, 1, 0, 'C' );

			$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
			$pdf->Cell( 30, 6, $summary['daysUsed'] . '/' . $summary['daysAllowed'], 1, 0, 'C' );
			$pdf->Cell( 25, 6, $summary['daysAllowed'], 1, 0, 'C' );
			$pdf->Cell( 30, 6, $summary['daysRemaining'] . ' days', 1, 1, 'C' );
		}

		$pdf->Ln( 8 );
	}

	/**
	 * Add detailed jurisdiction analysis.
	 *
	 * @param TCPDF $pdf     PDF instance.
	 * @param array $options Report options.
	 */
	private function add_jurisdiction_details( $pdf, $options ) {
		$jurisdictions = $this->get_user_jurisdictions( $options['jurisdictions'] );
		$trips         = $this->get_user_trips();

		foreach ( $jurisdictions as $j ) {
			// Check if we need a new page.
			if ( $pdf->GetY() > 220 ) {
				$pdf->AddPage();
			}

			$summary = $this->jurisdiction->calculate_summary(
				$trips,
				$j,
				$this->period_end
			);

			// Section title.
			$pdf->SetFillColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
			$pdf->SetTextColor( 255, 255, 255 );
			$pdf->SetFont( 'helvetica', 'B', 11 );
			$pdf->Cell( 0, 8, ' ' . $j['flagEmoji'] . ' ' . strtoupper( $j['name'] ), 1, 1, 'L', true );

			$pdf->Ln( 2 );
			$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );

			// Rule description.
			$pdf->SetFont( 'helvetica', 'B', 10 );
			$pdf->Cell( 0, 5, 'Rule Summary:', 0, 1, 'L' );
			$pdf->SetFont( 'helvetica', '', 9 );

			$rule_description = ! empty( $j['notes'] ) ? $j['notes'] : $j['description'];
			$pdf->MultiCell( 0, 5, $rule_description, 0, 'L' );
			$pdf->Ln( 2 );

			// Status summary.
			$pdf->SetFont( 'helvetica', 'B', 10 );
			$pdf->Cell( 0, 5, 'Current Status:', 0, 1, 'L' );
			$pdf->SetFont( 'helvetica', '', 9 );

			$status_lines = array(
				'Days in jurisdiction: ' . $summary['daysUsed'] . ' days',
				'Threshold: ' . $summary['daysAllowed'] . ' days',
				'Days remaining: ' . $summary['daysRemaining'] . ' days',
				'Status: ' . $this->get_status_text( $summary['status'] ),
			);

			foreach ( $status_lines as $line ) {
				$pdf->Cell( 5, 4, '', 0, 0 );
				$pdf->Cell( 0, 4, '• ' . $line, 0, 1, 'L' );
			}

			// Weighted breakdown for US SPT.
			if ( 'weighted_multi_year' === $j['countingMethod'] && ! empty( $summary['weightedBreakdown'] ) ) {
				$pdf->Ln( 2 );
				$pdf->SetFont( 'helvetica', 'B', 10 );
				$pdf->Cell( 0, 5, 'Weighted Calculation (SPT):', 0, 1, 'L' );
				$pdf->SetFont( 'helvetica', '', 9 );

				$breakdown = $summary['weightedBreakdown'];
				foreach ( $breakdown['years'] as $year_data ) {
					$line = $year_data['year'] . ': ' . $year_data['actualDays'] . ' days × ' .
						$year_data['weight'] . ' = ' . round( $year_data['weightedDays'], 1 );
					$pdf->Cell( 5, 4, '', 0, 0 );
					$pdf->Cell( 0, 4, '• ' . $line, 0, 1, 'L' );
				}
				$pdf->Cell( 5, 4, '', 0, 0 );
				$pdf->SetFont( 'helvetica', 'B', 9 );
				$pdf->Cell( 0, 4, 'Total Weighted: ' . $breakdown['totalWeighted'] . ' (threshold: 183)', 0, 1, 'L' );
			}

			// Multi-year breakdown for Ireland.
			if ( 'multi_year' === $j['countingMethod'] && ! empty( $summary['multiYearBreakdown'] ) ) {
				$pdf->Ln( 2 );
				$pdf->SetFont( 'helvetica', 'B', 10 );
				$pdf->Cell( 0, 5, 'Two-Year Calculation:', 0, 1, 'L' );
				$pdf->SetFont( 'helvetica', '', 9 );

				$breakdown = $summary['multiYearBreakdown'];
				$pdf->Cell( 5, 4, '', 0, 0 );
				$pdf->Cell( 0, 4, '• ' . $breakdown['currentYear']['year'] . ': ' . $breakdown['currentYear']['days'] . ' days', 0, 1, 'L' );
				$pdf->Cell( 5, 4, '', 0, 0 );
				$pdf->Cell( 0, 4, '• ' . $breakdown['priorYear']['year'] . ': ' . $breakdown['priorYear']['days'] . ' days', 0, 1, 'L' );
				$pdf->Cell( 5, 4, '', 0, 0 );
				$pdf->SetFont( 'helvetica', 'B', 9 );
				$pdf->Cell( 0, 4, 'Combined: ' . $breakdown['combinedDays'] . ' days (secondary threshold: ' . $breakdown['secondaryThreshold'] . ')', 0, 1, 'L' );
			}

			$pdf->Ln( 5 );
		}
	}

	/**
	 * Add detailed trip log.
	 *
	 * @param TCPDF $pdf     PDF instance.
	 * @param array $options Report options.
	 */
	private function add_trip_log( $pdf, $options ) {
		$pdf->AddPage();

		// Section title.
		$pdf->SetFillColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->Cell( 0, 8, ' DETAILED TRIP LOG', 1, 1, 'L', true );

		$pdf->Ln( 2 );

		$trips = $this->get_user_trips();

		if ( empty( $trips ) ) {
			$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
			$pdf->SetFont( 'helvetica', 'I', 10 );
			$pdf->Cell( 0, 6, 'No trips recorded in this period.', 0, 1, 'L' );
			return;
		}

		// Table header.
		$pdf->SetFillColor( 245, 247, 250 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->SetFont( 'helvetica', 'B', 8 );

		$pdf->Cell( 10, 7, '#', 1, 0, 'C', true );
		$pdf->Cell( 30, 7, 'Entry Date', 1, 0, 'C', true );
		$pdf->Cell( 30, 7, 'Exit Date', 1, 0, 'C', true );
		$pdf->Cell( 50, 7, 'Location', 1, 0, 'L', true );
		$pdf->Cell( 20, 7, 'Days', 1, 0, 'C', true );
		$pdf->Cell( 30, 7, 'Source', 1, 1, 'C', true );

		// Table rows.
		$pdf->SetFont( 'helvetica', '', 8 );
		$total_days = 0;
		$count      = 0;

		foreach ( $trips as $trip ) {
			$count++;

			// Check for page break.
			if ( $pdf->GetY() > 265 ) {
				$pdf->AddPage();
				$pdf->SetFillColor( 245, 247, 250 );
				$pdf->SetFont( 'helvetica', 'B', 8 );
				$pdf->Cell( 10, 7, '#', 1, 0, 'C', true );
				$pdf->Cell( 30, 7, 'Entry Date', 1, 0, 'C', true );
				$pdf->Cell( 30, 7, 'Exit Date', 1, 0, 'C', true );
				$pdf->Cell( 50, 7, 'Location', 1, 0, 'L', true );
				$pdf->Cell( 20, 7, 'Days', 1, 0, 'C', true );
				$pdf->Cell( 30, 7, 'Source', 1, 1, 'C', true );
				$pdf->SetFont( 'helvetica', '', 8 );
			}

			$start_date  = new DateTime( $trip['start_date'] );
			$end_date    = new DateTime( $trip['end_date'] );
			$days        = $end_date->diff( $start_date )->days + 1;
			$total_days += $days;

			$location = $trip['location'];
			if ( ! empty( $trip['country'] ) ) {
				$location = $trip['country'];
				if ( ! empty( $trip['location'] ) && $trip['location'] !== $trip['country'] ) {
					$location = $trip['location'] . ', ' . $trip['country'];
				}
			}

			$source = isset( $trip['source'] ) ? ucfirst( $trip['source'] ) : 'Manual';

			$pdf->Cell( 10, 5, $count, 1, 0, 'C' );
			$pdf->Cell( 30, 5, $start_date->format( 'M j, Y' ), 1, 0, 'C' );
			$pdf->Cell( 30, 5, $end_date->format( 'M j, Y' ), 1, 0, 'C' );
			$pdf->Cell( 50, 5, substr( $location, 0, 30 ), 1, 0, 'L' );
			$pdf->Cell( 20, 5, $days, 1, 0, 'C' );
			$pdf->Cell( 30, 5, $source, 1, 1, 'C' );
		}

		// Total row.
		$pdf->SetFont( 'helvetica', 'B', 8 );
		$pdf->SetFillColor( 245, 247, 250 );
		$pdf->Cell( 120, 6, 'TOTAL TRIPS: ' . $count, 1, 0, 'R', true );
		$pdf->Cell( 20, 6, $total_days, 1, 0, 'C', true );
		$pdf->Cell( 30, 6, '', 1, 1, 'C', true );

		$pdf->Ln( 5 );
	}

	/**
	 * Add calculation methodology section.
	 *
	 * @param TCPDF $pdf PDF instance.
	 */
	private function add_methodology( $pdf ) {
		if ( $pdf->GetY() > 200 ) {
			$pdf->AddPage();
		}

		// Section title.
		$pdf->SetFillColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->Cell( 0, 8, ' CALCULATION METHODOLOGY', 1, 1, 'L', true );

		$pdf->Ln( 2 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->SetFont( 'helvetica', 'B', 10 );
		$pdf->Cell( 0, 5, 'Day Counting Method:', 0, 1, 'L' );

		$pdf->SetFont( 'helvetica', '', 9 );
		$methods = array(
			'Entry day counted as Day 1',
			'Exit day counted as final day',
			'Partial days count as full days',
			'Overnight stays determine location',
		);

		foreach ( $methods as $method ) {
			$pdf->Cell( 5, 4, '', 0, 0 );
			$pdf->Cell( 0, 4, '• ' . $method, 0, 1, 'L' );
		}

		$pdf->Ln( 3 );
		$pdf->SetFont( 'helvetica', 'B', 10 );
		$pdf->Cell( 0, 5, 'Data Sources:', 0, 1, 'L' );

		$pdf->SetFont( 'helvetica', '', 9 );
		$sources = array(
			'Manual Entry: User-entered trips',
			'GPS Auto: Automatic location detection (3x daily)',
			'Calendar: Imported from Google/Microsoft calendar',
			'Photo GPS: Extracted from photo EXIF metadata',
		);

		foreach ( $sources as $source ) {
			$pdf->Cell( 5, 4, '', 0, 0 );
			$pdf->Cell( 0, 4, '• ' . $source, 0, 1, 'L' );
		}

		$pdf->Ln( 3 );
		$pdf->SetFont( 'helvetica', 'B', 10 );
		$pdf->Cell( 0, 5, 'Accuracy Notes:', 0, 1, 'L' );

		$pdf->SetFont( 'helvetica', '', 9 );
		$notes = array(
			'GPS accuracy: ±50 meters, converted to country only',
			'Calendar events may not reflect actual travel',
			'Photo timestamps depend on camera settings',
		);

		foreach ( $notes as $note ) {
			$pdf->Cell( 5, 4, '', 0, 0 );
			$pdf->Cell( 0, 4, '• ' . $note, 0, 1, 'L' );
		}

		$pdf->Ln( 5 );
	}

	/**
	 * Add legal disclaimer section.
	 *
	 * @param TCPDF $pdf PDF instance.
	 */
	private function add_disclaimer( $pdf ) {
		if ( $pdf->GetY() > 200 ) {
			$pdf->AddPage();
		}

		// Section title.
		$pdf->SetFillColor( $this->colors['danger'][0], $this->colors['danger'][1], $this->colors['danger'][2] );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->Cell( 0, 8, ' LEGAL DISCLAIMER', 1, 1, 'L', true );

		$pdf->Ln( 2 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->SetFont( 'helvetica', 'B', 10 );
		$pdf->Cell( 0, 5, 'IMPORTANT NOTICE', 0, 1, 'L' );

		$pdf->SetFont( 'helvetica', '', 9 );
		$disclaimer = 'This report is provided for INFORMATIONAL PURPOSES ONLY and does NOT constitute legal, tax, or immigration advice.

• Tax residency determination involves multiple factors beyond physical presence, including intention, family ties, property ownership, and economic interests.

• Immigration status depends on visa type, entry conditions, and individual circumstances.

• Always consult with qualified legal and tax professionals for advice specific to your situation.

• Border officials and tax authorities make final determinations at their discretion.

• MyTravelStatus does not guarantee the accuracy of calculations or compliance with any jurisdiction\'s rules.

USE THIS REPORT AS A HELPFUL REFERENCE, NOT AS LEGAL GUIDANCE.';

		$pdf->MultiCell( 0, 5, $disclaimer, 0, 'L' );

		$pdf->Ln( 5 );
	}

	/**
	 * Add verification section with QR code.
	 *
	 * @param TCPDF $pdf PDF instance.
	 */
	private function add_verification( $pdf ) {
		if ( $pdf->GetY() > 230 ) {
			$pdf->AddPage();
		}

		// Section title.
		$pdf->SetFillColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetFont( 'helvetica', 'B', 11 );
		$pdf->Cell( 0, 8, ' VERIFICATION', 1, 1, 'L', true );

		$pdf->Ln( 2 );
		$pdf->SetTextColor( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] );
		$pdf->SetFont( 'helvetica', '', 9 );

		$verification_url = home_url( '/mts-verify/' . $this->report_id );

		$pdf->Cell( 35, 5, 'Report ID:', 0, 0, 'L' );
		$pdf->Cell( 0, 5, $this->report_id, 0, 1, 'L' );

		$pdf->Cell( 35, 5, 'Generated By:', 0, 0, 'L' );
		$pdf->Cell( 0, 5, 'MyTravelStatus v' . MTS_VERSION, 0, 1, 'L' );

		$pdf->Cell( 35, 5, 'Verification:', 0, 0, 'L' );
		$pdf->SetTextColor( $this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2] );
		$pdf->Cell( 0, 5, $verification_url, 0, 1, 'L' );

		// QR Code.
		$pdf->Ln( 3 );
		$style = array(
			'border'        => false,
			'padding'       => 0,
			'fgcolor'       => array( $this->colors['text'][0], $this->colors['text'][1], $this->colors['text'][2] ),
			'bgcolor'       => false,
		);
		$pdf->write2DBarcode( $verification_url, 'QRCODE,L', $pdf->GetX(), $pdf->GetY(), 30, 30, $style );

		// Footer line.
		$pdf->Ln( 35 );
		$pdf->SetDrawColor( $this->colors['light'][0], $this->colors['light'][1], $this->colors['light'][2] );
		$pdf->Line( 15, $pdf->GetY(), 195, $pdf->GetY() );
		$pdf->Ln( 3 );

		$pdf->SetTextColor( $this->colors['light'][0], $this->colors['light'][1], $this->colors['light'][2] );
		$pdf->SetFont( 'helvetica', '', 8 );
		$pdf->Cell( 0, 4, '© ' . gmdate( 'Y' ) . ' MyTravelStatus  |  Privacy Policy  |  Terms of Service', 0, 1, 'C' );
	}

	/**
	 * Get user's enabled jurisdictions.
	 *
	 * @param array $filter Optional filter for specific jurisdictions.
	 * @return array Jurisdictions.
	 */
	private function get_user_jurisdictions( $filter = array() ) {
		$jurisdictions = $this->jurisdiction->get_user_jurisdictions( $this->user_id );

		if ( ! empty( $filter ) ) {
			$jurisdictions = array_filter(
				$jurisdictions,
				function ( $j ) use ( $filter ) {
					return in_array( $j['code'], $filter, true );
				}
			);
		}

		return array_values( $jurisdictions );
	}

	/**
	 * Get user's trips within period.
	 *
	 * @return array Trips.
	 */
	private function get_user_trips() {
		global $wpdb;

		$table = $wpdb->prefix . 'mts_trips';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE user_id = %d
				AND start_date <= %s
				AND end_date >= %s
				ORDER BY start_date ASC",
				$this->user_id,
				$this->period_end->format( 'Y-m-d' ),
				$this->period_start->format( 'Y-m-d' )
			),
			ARRAY_A
		);

		return $trips ? $trips : array();
	}

	/**
	 * Get status text label.
	 *
	 * @param string $status Status code.
	 * @return string Status text.
	 */
	private function get_status_text( $status ) {
		$labels = array(
			'ok'       => '✓ OK',
			'warning'  => '⚠ WARNING',
			'critical' => '⚠ CRITICAL',
			'exceeded' => '✗ EXCEEDED',
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Get status color.
	 *
	 * @param string $status Status code.
	 * @return array RGB color.
	 */
	private function get_status_color( $status ) {
		$colors = array(
			'ok'       => $this->colors['success'],
			'warning'  => $this->colors['warning'],
			'critical' => $this->colors['danger'],
			'exceeded' => $this->colors['danger'],
		);
		return isset( $colors[ $status ] ) ? $colors[ $status ] : $this->colors['text'];
	}

	/**
	 * Store report metadata in database.
	 *
	 * @param string $file_path File path.
	 * @param string $hash      File hash.
	 * @param array  $options   Report options.
	 */
	private function store_report_metadata( $file_path, $hash, $options ) {
		global $wpdb;

		$table = $wpdb->prefix . 'mts_reports';

		// Create table if not exists.
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				report_id VARCHAR(50) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				file_path VARCHAR(500) NOT NULL,
				file_hash VARCHAR(64) NOT NULL,
				period_start DATE NOT NULL,
				period_end DATE NOT NULL,
				options TEXT,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY report_id (report_id),
				INDEX user_id (user_id)
			) {$charset_collate}"
		);

		// Insert report record.
		$wpdb->insert(
			$table,
			array(
				'report_id'    => $this->report_id,
				'user_id'      => $this->user_id,
				'file_path'    => $file_path,
				'file_hash'    => $hash,
				'period_start' => $this->period_start->format( 'Y-m-d' ),
				'period_end'   => $this->period_end->format( 'Y-m-d' ),
				'options'      => wp_json_encode( $options ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Verify a report by ID.
	 *
	 * @param string $report_id Report ID.
	 * @return array|WP_Error Verification result.
	 */
	public static function verify_report( $report_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'mts_reports';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$report = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE report_id = %s",
				$report_id
			),
			ARRAY_A
		);

		if ( ! $report ) {
			return new WP_Error(
				'report_not_found',
				'Report not found.',
				array( 'status' => 404 )
			);
		}

		// Verify file exists and hash matches.
		$file_valid = false;
		$hash_valid = false;

		if ( file_exists( $report['file_path'] ) ) {
			$file_valid   = true;
			$current_hash = hash_file( 'sha256', $report['file_path'] );
			$hash_valid   = ( $current_hash === $report['file_hash'] );
		}

		return array(
			'report_id'    => $report['report_id'],
			'user_id'      => (int) $report['user_id'],
			'period_start' => $report['period_start'],
			'period_end'   => $report['period_end'],
			'created_at'   => $report['created_at'],
			'file_exists'  => $file_valid,
			'hash_valid'   => $hash_valid,
			'verified'     => $file_valid && $hash_valid,
		);
	}
}
