<?php
/**
 * Dynamic Checklist Generator
 *
 * Generates personalized checklists based on visa type
 * and user circumstances.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get checklist for a visa type
 *
 * @param string $visa_type Visa type identifier
 * @return array Checklist phases and tasks
 */
function fra_get_checklist($visa_type) {
    $checklists = fra_get_all_checklists();

    if (empty($visa_type) || !isset($checklists[$visa_type])) {
        return $checklists['visitor'] ?? array(); // Default to visitor
    }

    return $checklists[$visa_type];
}

/**
 * Get all checklists by visa type
 *
 * @return array All checklists
 */
function fra_get_all_checklists() {
    return array(
        'visitor' => array(
            array(
                'phase' => 'planning',
                'title' => 'Planning Phase',
                'subtitle' => '6-12 months before',
                'tasks' => array(
                    array('id' => 'v-research', 'title' => 'Research visa requirements', 'link' => '/guides/visitor-visa-requirements/'),
                    array('id' => 'v-finances', 'title' => 'Calculate budget and financial requirements', 'link' => null),
                    array('id' => 'v-timeline', 'title' => 'Create relocation timeline', 'link' => null),
                    array('id' => 'v-region', 'title' => 'Research destination regions', 'link' => null),
                    array('id' => 'v-housing', 'title' => 'Start housing search', 'link' => '/guides/buying-property-france/')
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Preparation Phase',
                'subtitle' => '3-6 months before',
                'tasks' => array(
                    array('id' => 'v-documents', 'title' => 'Gather required documents', 'link' => null),
                    array('id' => 'v-passport', 'title' => 'Check passport validity (6+ months)', 'link' => null),
                    array('id' => 'v-insurance', 'title' => 'Obtain health insurance', 'link' => '/guides/french-healthcare-overview/'),
                    array('id' => 'v-income', 'title' => 'Prepare proof of income/resources', 'link' => null),
                    array('id' => 'v-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 'v-apply', 'title' => 'Submit visa application', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Moving Phase',
                'subtitle' => '1-2 months before',
                'tasks' => array(
                    array('id' => 'v-flights', 'title' => 'Book flights', 'link' => null),
                    array('id' => 'v-shipping', 'title' => 'Arrange shipping/storage', 'link' => null),
                    array('id' => 'v-accommodation', 'title' => 'Secure initial accommodation', 'link' => null),
                    array('id' => 'v-notify', 'title' => 'Notify banks, subscriptions, etc.', 'link' => null),
                    array('id' => 'v-mail', 'title' => 'Set up mail forwarding', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Settling In Phase',
                'subtitle' => 'First 3 months',
                'tasks' => array(
                    array('id' => 'v-validate', 'title' => 'Validate visa (VLS-TS)', 'link' => null),
                    array('id' => 'v-bank', 'title' => 'Open French bank account', 'link' => '/guides/french-bank-account/'),
                    array('id' => 'v-cpam', 'title' => 'Register with CPAM', 'link' => '/guides/carte-vitale-application/'),
                    array('id' => 'v-phone', 'title' => 'Get French phone number', 'link' => null),
                    array('id' => 'v-utilities', 'title' => 'Set up utilities', 'link' => null),
                    array('id' => 'v-residence', 'title' => 'Track days for tax residency', 'link' => '/guides/tax-residency-rules/')
                )
            )
        ),

        'talent' => array(
            array(
                'phase' => 'planning',
                'title' => 'Planning Phase',
                'subtitle' => '3-6 months before',
                'tasks' => array(
                    array('id' => 't-research', 'title' => 'Research Talent Passport categories', 'link' => null),
                    array('id' => 't-employment', 'title' => 'Secure employment/contract', 'link' => null),
                    array('id' => 't-documents', 'title' => 'Gather qualification documents', 'link' => null),
                    array('id' => 't-salary', 'title' => 'Verify salary meets requirements', 'link' => null)
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Visa Application',
                'subtitle' => '2-3 months before',
                'tasks' => array(
                    array('id' => 't-contract', 'title' => 'Finalize work contract', 'link' => null),
                    array('id' => 't-diplomas', 'title' => 'Apostille diplomas if needed', 'link' => null),
                    array('id' => 't-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 't-apply', 'title' => 'Submit Talent Passport application', 'link' => null),
                    array('id' => 't-family', 'title' => 'Apply for family visas if applicable', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Relocation',
                'subtitle' => '1 month before',
                'tasks' => array(
                    array('id' => 't-flights', 'title' => 'Book flights', 'link' => null),
                    array('id' => 't-housing', 'title' => 'Secure housing', 'link' => null),
                    array('id' => 't-shipping', 'title' => 'Arrange belongings shipping', 'link' => null),
                    array('id' => 't-employer', 'title' => 'Coordinate start date with employer', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Integration',
                'subtitle' => 'First 3 months',
                'tasks' => array(
                    array('id' => 't-prefecture', 'title' => 'Register at prefecture', 'link' => null),
                    array('id' => 't-bank', 'title' => 'Open bank account', 'link' => '/guides/french-bank-account/'),
                    array('id' => 't-secu', 'title' => 'Get social security number', 'link' => null),
                    array('id' => 't-cpam', 'title' => 'Register with CPAM', 'link' => '/guides/carte-vitale-application/'),
                    array('id' => 't-taxes', 'title' => 'Set up tax obligations', 'link' => '/guides/tax-residency-rules/')
                )
            )
        ),

        'student' => array(
            array(
                'phase' => 'planning',
                'title' => 'Planning Phase',
                'subtitle' => '6-12 months before',
                'tasks' => array(
                    array('id' => 's-research', 'title' => 'Research French universities/programs', 'link' => null),
                    array('id' => 's-apply', 'title' => 'Apply to educational institutions', 'link' => null),
                    array('id' => 's-campusfrance', 'title' => 'Register on Campus France', 'link' => null),
                    array('id' => 's-acceptance', 'title' => 'Receive acceptance letter', 'link' => null)
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Visa Application',
                'subtitle' => '2-3 months before',
                'tasks' => array(
                    array('id' => 's-documents', 'title' => 'Gather required documents', 'link' => null),
                    array('id' => 's-finances', 'title' => 'Prove financial resources', 'link' => null),
                    array('id' => 's-insurance', 'title' => 'Obtain health insurance', 'link' => null),
                    array('id' => 's-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 's-submit', 'title' => 'Submit student visa application', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Moving Phase',
                'subtitle' => '1 month before',
                'tasks' => array(
                    array('id' => 's-flights', 'title' => 'Book flights', 'link' => null),
                    array('id' => 's-housing', 'title' => 'Secure student housing', 'link' => null),
                    array('id' => 's-packing', 'title' => 'Pack essentials', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Settling In',
                'subtitle' => 'First month',
                'tasks' => array(
                    array('id' => 's-validate', 'title' => 'Validate visa online', 'link' => null),
                    array('id' => 's-bank', 'title' => 'Open French bank account', 'link' => '/guides/french-bank-account/'),
                    array('id' => 's-caf', 'title' => 'Apply for CAF housing assistance', 'link' => null),
                    array('id' => 's-phone', 'title' => 'Get French phone number', 'link' => null),
                    array('id' => 's-register', 'title' => 'Complete university registration', 'link' => null)
                )
            )
        ),

        'family' => array(
            array(
                'phase' => 'planning',
                'title' => 'Planning Phase',
                'subtitle' => '3-6 months before',
                'tasks' => array(
                    array('id' => 'f-eligibility', 'title' => 'Verify eligibility for family reunification', 'link' => null),
                    array('id' => 'f-sponsor', 'title' => 'Confirm sponsor meets requirements', 'link' => null),
                    array('id' => 'f-documents', 'title' => 'Gather family documents', 'link' => null)
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Application Phase',
                'subtitle' => '2-4 months before',
                'tasks' => array(
                    array('id' => 'f-ofii', 'title' => 'Sponsor submits OFII application', 'link' => null),
                    array('id' => 'f-approval', 'title' => 'Receive OFII approval', 'link' => null),
                    array('id' => 'f-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 'f-apply', 'title' => 'Submit family visa application', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Relocation',
                'subtitle' => '1 month before',
                'tasks' => array(
                    array('id' => 'f-flights', 'title' => 'Book flights for family', 'link' => null),
                    array('id' => 'f-housing', 'title' => 'Prepare housing for family', 'link' => null),
                    array('id' => 'f-schools', 'title' => 'Research schools for children', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Integration',
                'subtitle' => 'First 3 months',
                'tasks' => array(
                    array('id' => 'f-validate', 'title' => 'Validate visa at OFII', 'link' => null),
                    array('id' => 'f-bank', 'title' => 'Open bank accounts', 'link' => '/guides/french-bank-account/'),
                    array('id' => 'f-cpam', 'title' => 'Register family with CPAM', 'link' => '/guides/carte-vitale-application/'),
                    array('id' => 'f-enroll', 'title' => 'Enroll children in school', 'link' => null)
                )
            )
        ),

        'retiree' => array(
            array(
                'phase' => 'planning',
                'title' => 'Planning Phase',
                'subtitle' => '6-12 months before',
                'tasks' => array(
                    array('id' => 'r-research', 'title' => 'Research visitor visa for retirees', 'link' => '/guides/visitor-visa-requirements/'),
                    array('id' => 'r-finances', 'title' => 'Document pension/retirement income', 'link' => null),
                    array('id' => 'r-region', 'title' => 'Research retirement destinations', 'link' => null),
                    array('id' => 'r-property', 'title' => 'Consider property purchase', 'link' => '/guides/buying-property-france/')
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Preparation Phase',
                'subtitle' => '3-6 months before',
                'tasks' => array(
                    array('id' => 'r-documents', 'title' => 'Gather required documents', 'link' => null),
                    array('id' => 'r-insurance', 'title' => 'Obtain comprehensive health insurance', 'link' => '/guides/french-healthcare-overview/'),
                    array('id' => 'r-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 'r-apply', 'title' => 'Submit visa application', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Moving Phase',
                'subtitle' => '1-2 months before',
                'tasks' => array(
                    array('id' => 'r-flights', 'title' => 'Book flights', 'link' => null),
                    array('id' => 'r-shipping', 'title' => 'Arrange shipping of belongings', 'link' => null),
                    array('id' => 'r-housing', 'title' => 'Finalize housing arrangements', 'link' => null),
                    array('id' => 'r-notify', 'title' => 'Notify pension providers of move', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Settling In',
                'subtitle' => 'First 3 months',
                'tasks' => array(
                    array('id' => 'r-validate', 'title' => 'Validate visa (VLS-TS)', 'link' => null),
                    array('id' => 'r-bank', 'title' => 'Open French bank account', 'link' => '/guides/french-bank-account/'),
                    array('id' => 'r-cpam', 'title' => 'Register with CPAM', 'link' => '/guides/carte-vitale-application/'),
                    array('id' => 'r-taxes', 'title' => 'Understand tax obligations', 'link' => '/guides/tax-residency-rules/'),
                    array('id' => 'r-doctors', 'title' => 'Find local doctors', 'link' => null)
                )
            )
        ),

        'other' => array(
            array(
                'phase' => 'planning',
                'title' => 'Research Phase',
                'subtitle' => '6+ months before',
                'tasks' => array(
                    array('id' => 'o-visa', 'title' => 'Determine appropriate visa type', 'link' => '/guides/long-stay-visa-overview/'),
                    array('id' => 'o-requirements', 'title' => 'Research specific requirements', 'link' => null),
                    array('id' => 'o-timeline', 'title' => 'Create realistic timeline', 'link' => null)
                )
            ),
            array(
                'phase' => 'preparing',
                'title' => 'Preparation',
                'subtitle' => '3-6 months before',
                'tasks' => array(
                    array('id' => 'o-documents', 'title' => 'Gather all required documents', 'link' => null),
                    array('id' => 'o-insurance', 'title' => 'Obtain health insurance', 'link' => '/guides/french-healthcare-overview/'),
                    array('id' => 'o-appointment', 'title' => 'Schedule visa appointment', 'link' => null),
                    array('id' => 'o-apply', 'title' => 'Submit visa application', 'link' => null)
                )
            ),
            array(
                'phase' => 'moving',
                'title' => 'Relocation',
                'subtitle' => '1-2 months before',
                'tasks' => array(
                    array('id' => 'o-flights', 'title' => 'Book travel', 'link' => null),
                    array('id' => 'o-housing', 'title' => 'Arrange housing', 'link' => null),
                    array('id' => 'o-logistics', 'title' => 'Handle logistics', 'link' => null)
                )
            ),
            array(
                'phase' => 'settling',
                'title' => 'Settling In',
                'subtitle' => 'First 3 months',
                'tasks' => array(
                    array('id' => 'o-validate', 'title' => 'Complete visa validation', 'link' => null),
                    array('id' => 'o-bank', 'title' => 'Open French bank account', 'link' => '/guides/french-bank-account/'),
                    array('id' => 'o-admin', 'title' => 'Handle administrative tasks', 'link' => null)
                )
            )
        )
    );
}

/**
 * Get available visa types
 *
 * @return array Visa types with labels
 */
function fra_get_visa_types() {
    return array(
        'visitor' => 'Visitor Visa (Non-Working)',
        'talent'  => 'Talent Passport',
        'student' => 'Student Visa',
        'family'  => 'Family Reunification',
        'retiree' => 'Retiree Visa',
        'other'   => 'Other / Not Sure'
    );
}
