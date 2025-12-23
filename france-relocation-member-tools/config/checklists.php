<?php
/**
 * Checklist Configuration
 *
 * Contains all checklist types and their items.
 * These are static data used across the application.
 *
 * @package FRA_Member_Tools
 * @since   2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get checklist type definitions
 *
 * @return array Checklist types with metadata.
 */
function framt_get_checklist_types(): array {
    return array(
        'visa-application' => array(
            'id'          => 'visa-application',
            'title'       => 'Visa Application Checklist',
            'description' => 'Documents and steps needed for your visa application',
            'icon'        => 'FileText',
            'category'    => 'visa',
        ),
        'pre-departure' => array(
            'id'          => 'pre-departure',
            'title'       => 'Pre-Departure Checklist',
            'description' => 'Things to complete before leaving your home country',
            'icon'        => 'Plane',
            'category'    => 'travel',
        ),
        'arrival' => array(
            'id'          => 'arrival',
            'title'       => 'Arrival Checklist',
            'description' => 'First steps after arriving in France',
            'icon'        => 'MapPin',
            'category'    => 'settlement',
        ),
        'administrative' => array(
            'id'          => 'administrative',
            'title'       => 'Administrative Setup',
            'description' => 'French administrative tasks and registrations',
            'icon'        => 'Building',
            'category'    => 'admin',
        ),
        'banking' => array(
            'id'          => 'banking',
            'title'       => 'Banking & Finance',
            'description' => 'Setting up French bank accounts and finances',
            'icon'        => 'CreditCard',
            'category'    => 'finance',
        ),
        'healthcare' => array(
            'id'          => 'healthcare',
            'title'       => 'Healthcare Setup',
            'description' => 'Registering for French healthcare system',
            'icon'        => 'Heart',
            'category'    => 'health',
        ),
    );
}

/**
 * Get checklist items for a specific type
 *
 * @param string $type Checklist type.
 * @return array Checklist items.
 */
function framt_get_checklist_items( string $type ): array {
    $items = array(
        'visa-application' => array(
            array( 'id' => 'passport-valid', 'title' => 'Valid passport (6+ months)', 'lead_time' => 180, 'priority' => 'high' ),
            array( 'id' => 'passport-photos', 'title' => 'Passport photos (35x45mm)', 'lead_time' => 14, 'priority' => 'high' ),
            array( 'id' => 'application-form', 'title' => 'Completed application form', 'lead_time' => 7, 'priority' => 'high' ),
            array( 'id' => 'proof-accommodation', 'title' => 'Proof of accommodation in France', 'lead_time' => 30, 'priority' => 'high' ),
            array( 'id' => 'proof-funds', 'title' => 'Proof of sufficient funds', 'lead_time' => 30, 'priority' => 'high' ),
            array( 'id' => 'travel-insurance', 'title' => 'Travel/health insurance certificate', 'lead_time' => 14, 'priority' => 'high' ),
            array( 'id' => 'flight-reservation', 'title' => 'Flight reservation/itinerary', 'lead_time' => 14, 'priority' => 'medium' ),
            array( 'id' => 'cover-letter', 'title' => 'Cover letter explaining purpose', 'lead_time' => 7, 'priority' => 'medium' ),
            array( 'id' => 'employment-proof', 'title' => 'Employment/income proof', 'lead_time' => 14, 'priority' => 'medium' ),
            array( 'id' => 'bank-statements', 'title' => 'Bank statements (3-6 months)', 'lead_time' => 7, 'priority' => 'high' ),
        ),
        'pre-departure' => array(
            array( 'id' => 'visa-received', 'title' => 'Visa received and verified', 'lead_time' => 30, 'priority' => 'high' ),
            array( 'id' => 'flights-booked', 'title' => 'Flights booked', 'lead_time' => 60, 'priority' => 'high' ),
            array( 'id' => 'accommodation-first', 'title' => 'First accommodation arranged', 'lead_time' => 30, 'priority' => 'high' ),
            array( 'id' => 'phone-plan', 'title' => 'International phone plan/SIM', 'lead_time' => 7, 'priority' => 'medium' ),
            array( 'id' => 'bank-notify', 'title' => 'Notify bank of travel', 'lead_time' => 7, 'priority' => 'medium' ),
            array( 'id' => 'important-docs', 'title' => 'Copies of important documents', 'lead_time' => 7, 'priority' => 'high' ),
            array( 'id' => 'prescriptions', 'title' => 'Prescription medications (3 months)', 'lead_time' => 14, 'priority' => 'medium' ),
            array( 'id' => 'power-attorney', 'title' => 'Power of attorney (if needed)', 'lead_time' => 30, 'priority' => 'low' ),
        ),
        'arrival' => array(
            array( 'id' => 'validate-visa', 'title' => 'Validate visa online (VLS-TS)', 'lead_time' => 90, 'priority' => 'high' ),
            array( 'id' => 'french-sim', 'title' => 'Get French SIM card', 'lead_time' => 3, 'priority' => 'high' ),
            array( 'id' => 'transport-card', 'title' => 'Get transport card (Navigo, etc.)', 'lead_time' => 7, 'priority' => 'medium' ),
            array( 'id' => 'grocery-essentials', 'title' => 'Stock up on essentials', 'lead_time' => 3, 'priority' => 'medium' ),
            array( 'id' => 'neighborhood-explore', 'title' => 'Explore neighborhood', 'lead_time' => 7, 'priority' => 'low' ),
        ),
        'administrative' => array(
            array( 'id' => 'ofii-appointment', 'title' => 'Schedule OFII appointment', 'lead_time' => 90, 'priority' => 'high' ),
            array( 'id' => 'titre-sejour', 'title' => 'Apply for titre de séjour', 'lead_time' => 60, 'priority' => 'high' ),
            array( 'id' => 'caf-register', 'title' => 'Register with CAF (if eligible)', 'lead_time' => 30, 'priority' => 'medium' ),
            array( 'id' => 'tax-registration', 'title' => 'Register with tax authorities', 'lead_time' => 90, 'priority' => 'medium' ),
        ),
        'banking' => array(
            array( 'id' => 'bank-account', 'title' => 'Open French bank account', 'lead_time' => 14, 'priority' => 'high' ),
            array( 'id' => 'rib-obtained', 'title' => 'Obtain RIB (bank details)', 'lead_time' => 7, 'priority' => 'high' ),
            array( 'id' => 'card-received', 'title' => 'Receive debit card', 'lead_time' => 14, 'priority' => 'medium' ),
            array( 'id' => 'transfer-setup', 'title' => 'Set up money transfer method', 'lead_time' => 7, 'priority' => 'medium' ),
        ),
        'healthcare' => array(
            array( 'id' => 'cpam-register', 'title' => 'Register with CPAM (Ameli)', 'lead_time' => 90, 'priority' => 'high' ),
            array( 'id' => 'carte-vitale', 'title' => 'Apply for Carte Vitale', 'lead_time' => 60, 'priority' => 'high' ),
            array( 'id' => 'medecin-traitant', 'title' => 'Choose médecin traitant', 'lead_time' => 30, 'priority' => 'medium' ),
            array( 'id' => 'mutuelle', 'title' => 'Consider complementary insurance', 'lead_time' => 30, 'priority' => 'low' ),
        ),
    );

    return $items[ $type ] ?? array();
}
