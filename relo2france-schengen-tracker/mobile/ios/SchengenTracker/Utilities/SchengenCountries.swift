/**
 * SchengenCountries.swift
 *
 * Reference data for Schengen zone countries.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation

struct SchengenCountries {
    /// ISO country codes for Schengen members
    static let codes: Set<String> = [
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
        "CH", // Switzerland
    ]

    /// Country names mapped to codes
    static let names: [String: String] = [
        "AT": "Austria",
        "BE": "Belgium",
        "BG": "Bulgaria",
        "HR": "Croatia",
        "CZ": "Czech Republic",
        "DK": "Denmark",
        "EE": "Estonia",
        "FI": "Finland",
        "FR": "France",
        "DE": "Germany",
        "GR": "Greece",
        "HU": "Hungary",
        "IS": "Iceland",
        "IT": "Italy",
        "LV": "Latvia",
        "LI": "Liechtenstein",
        "LT": "Lithuania",
        "LU": "Luxembourg",
        "MT": "Malta",
        "NL": "Netherlands",
        "NO": "Norway",
        "PL": "Poland",
        "PT": "Portugal",
        "RO": "Romania",
        "SK": "Slovakia",
        "SI": "Slovenia",
        "ES": "Spain",
        "SE": "Sweden",
        "CH": "Switzerland",
    ]

    /// Flag emojis for countries
    static let flags: [String: String] = [
        "AT": "🇦🇹",
        "BE": "🇧🇪",
        "BG": "🇧🇬",
        "HR": "🇭🇷",
        "CZ": "🇨🇿",
        "DK": "🇩🇰",
        "EE": "🇪🇪",
        "FI": "🇫🇮",
        "FR": "🇫🇷",
        "DE": "🇩🇪",
        "GR": "🇬🇷",
        "HU": "🇭🇺",
        "IS": "🇮🇸",
        "IT": "🇮🇹",
        "LV": "🇱🇻",
        "LI": "🇱🇮",
        "LT": "🇱🇹",
        "LU": "🇱🇺",
        "MT": "🇲🇹",
        "NL": "🇳🇱",
        "NO": "🇳🇴",
        "PL": "🇵🇱",
        "PT": "🇵🇹",
        "RO": "🇷🇴",
        "SK": "🇸🇰",
        "SI": "🇸🇮",
        "ES": "🇪🇸",
        "SE": "🇸🇪",
        "CH": "🇨🇭",
    ]

    /// Check if a country code is in Schengen
    static func isSchengen(code: String) -> Bool {
        codes.contains(code.uppercased())
    }

    /// Get country name for a code
    static func nameFor(code: String) -> String? {
        names[code.uppercased()]
    }

    /// Get flag emoji for a code
    static func flagFor(code: String) -> String {
        flags[code.uppercased()] ?? "🏳️"
    }

    /// Get code for a country name
    static func codeFor(name: String) -> String? {
        names.first { $0.value == name }?.key
    }

    /// All countries as an array of (code, name) tuples sorted by name
    static var sortedList: [(code: String, name: String)] {
        names.map { ($0.key, $0.value) }.sorted { $0.1 < $1.1 }
    }
}
