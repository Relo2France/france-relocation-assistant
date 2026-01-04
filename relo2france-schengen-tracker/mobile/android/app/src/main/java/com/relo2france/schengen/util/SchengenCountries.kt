/**
 * SchengenCountries.kt
 *
 * Reference data for Schengen zone countries.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.util

object SchengenCountries {
    /**
     * ISO country codes for Schengen members
     */
    val codes: Set<String> = setOf(
        "AT", // Austria
        "BE", // Belgium
        "BG", // Bulgaria
        "HR", // Croatia
        "CZ", // Czech Republic
        "DK", // Denmark
        "EE", // Estonia
        "FI", // Finland
        "FR", // France
        "DE", // Germany
        "GR", // Greece
        "HU", // Hungary
        "IS", // Iceland
        "IT", // Italy
        "LV", // Latvia
        "LI", // Liechtenstein
        "LT", // Lithuania
        "LU", // Luxembourg
        "MT", // Malta
        "NL", // Netherlands
        "NO", // Norway
        "PL", // Poland
        "PT", // Portugal
        "RO", // Romania
        "SK", // Slovakia
        "SI", // Slovenia
        "ES", // Spain
        "SE", // Sweden
        "CH"  // Switzerland
    )

    /**
     * Country names mapped to codes
     */
    val names: Map<String, String> = mapOf(
        "AT" to "Austria",
        "BE" to "Belgium",
        "BG" to "Bulgaria",
        "HR" to "Croatia",
        "CZ" to "Czech Republic",
        "DK" to "Denmark",
        "EE" to "Estonia",
        "FI" to "Finland",
        "FR" to "France",
        "DE" to "Germany",
        "GR" to "Greece",
        "HU" to "Hungary",
        "IS" to "Iceland",
        "IT" to "Italy",
        "LV" to "Latvia",
        "LI" to "Liechtenstein",
        "LT" to "Lithuania",
        "LU" to "Luxembourg",
        "MT" to "Malta",
        "NL" to "Netherlands",
        "NO" to "Norway",
        "PL" to "Poland",
        "PT" to "Portugal",
        "RO" to "Romania",
        "SK" to "Slovakia",
        "SI" to "Slovenia",
        "ES" to "Spain",
        "SE" to "Sweden",
        "CH" to "Switzerland"
    )

    /**
     * Flag emojis for countries
     */
    val flags: Map<String, String> = mapOf(
        "AT" to "\uD83C\uDDE6\uD83C\uDDF9",
        "BE" to "\uD83C\uDDE7\uD83C\uDDEA",
        "BG" to "\uD83C\uDDE7\uD83C\uDDEC",
        "HR" to "\uD83C\uDDED\uD83C\uDDF7",
        "CZ" to "\uD83C\uDDE8\uD83C\uDDFF",
        "DK" to "\uD83C\uDDE9\uD83C\uDDF0",
        "EE" to "\uD83C\uDDEA\uD83C\uDDEA",
        "FI" to "\uD83C\uDDEB\uD83C\uDDEE",
        "FR" to "\uD83C\uDDEB\uD83C\uDDF7",
        "DE" to "\uD83C\uDDE9\uD83C\uDDEA",
        "GR" to "\uD83C\uDDEC\uD83C\uDDF7",
        "HU" to "\uD83C\uDDED\uD83C\uDDFA",
        "IS" to "\uD83C\uDDEE\uD83C\uDDF8",
        "IT" to "\uD83C\uDDEE\uD83C\uDDF9",
        "LV" to "\uD83C\uDDF1\uD83C\uDDFB",
        "LI" to "\uD83C\uDDF1\uD83C\uDDEE",
        "LT" to "\uD83C\uDDF1\uD83C\uDDF9",
        "LU" to "\uD83C\uDDF1\uD83C\uDDFA",
        "MT" to "\uD83C\uDDF2\uD83C\uDDF9",
        "NL" to "\uD83C\uDDF3\uD83C\uDDF1",
        "NO" to "\uD83C\uDDF3\uD83C\uDDF4",
        "PL" to "\uD83C\uDDF5\uD83C\uDDF1",
        "PT" to "\uD83C\uDDF5\uD83C\uDDF9",
        "RO" to "\uD83C\uDDF7\uD83C\uDDF4",
        "SK" to "\uD83C\uDDF8\uD83C\uDDF0",
        "SI" to "\uD83C\uDDF8\uD83C\uDDEE",
        "ES" to "\uD83C\uDDEA\uD83C\uDDF8",
        "SE" to "\uD83C\uDDF8\uD83C\uDDEA",
        "CH" to "\uD83C\uDDE8\uD83C\uDDED"
    )

    /**
     * Check if a country code is in Schengen
     */
    fun isSchengen(code: String): Boolean {
        return codes.contains(code.uppercase())
    }

    /**
     * Get country name for a code
     */
    fun nameFor(code: String): String? {
        return names[code.uppercase()]
    }

    /**
     * Get flag emoji for a code
     */
    fun flagFor(code: String): String {
        return flags[code.uppercase()] ?: "\uD83C\uDFF3\uFE0F"
    }

    /**
     * Get code for a country name
     */
    fun codeFor(name: String): String? {
        return names.entries.find { it.value == name }?.key
    }

    /**
     * All countries as a list sorted by name
     */
    val sortedList: List<Pair<String, String>>
        get() = names.toList().sortedBy { it.second }
}
