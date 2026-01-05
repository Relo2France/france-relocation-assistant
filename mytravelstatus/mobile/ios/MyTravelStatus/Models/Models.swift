/**
 * Models.swift
 *
 * Core data models for the MyTravelStatus iOS app.
 * Matches the shared TypeScript types and API responses.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation

// MARK: - Trip

struct Trip: Codable, Identifiable {
    var id: Int
    var userId: Int?
    var familyMemberId: Int?
    var startDate: Date
    var endDate: Date
    var country: String
    var jurisdictionCode: String?
    var category: TripCategory
    var notes: String?
    var locationSource: LocationSource?
    var locationLat: Double?
    var locationLng: Double?
    var locationAccuracy: Double?
    var locationTimestamp: Date?
    var createdAt: Date?
    var updatedAt: Date?

    // Local-only properties
    var localId: String?
    var syncStatus: SyncStatus?

    enum TripCategory: String, Codable {
        case personal
        case business
    }

    init(id: Int = 0,
         startDate: Date,
         endDate: Date,
         country: String,
         category: TripCategory = .personal,
         notes: String? = nil,
         locationSource: LocationSource? = nil,
         locationLat: Double? = nil,
         locationLng: Double? = nil) {
        self.id = id
        self.startDate = startDate
        self.endDate = endDate
        self.country = country
        self.category = category
        self.notes = notes
        self.locationSource = locationSource
        self.locationLat = locationLat
        self.locationLng = locationLng
        self.localId = UUID().uuidString
        self.syncStatus = .pending
    }
}

// MARK: - Location

struct LocationReading: Codable, Identifiable {
    var id: Int?
    var lat: Double
    var lng: Double
    var accuracy: Double?
    var countryCode: String?
    var countryName: String?
    var city: String?
    var isSchengen: Bool
    var source: LocationSource
    var recordedAt: Date

    // Local-only properties
    var localId: String?
    var syncStatus: SyncStatus?

    init(lat: Double,
         lng: Double,
         accuracy: Double?,
         countryCode: String?,
         countryName: String?,
         city: String?,
         isSchengen: Bool,
         source: LocationSource,
         recordedAt: Date) {
        self.lat = lat
        self.lng = lng
        self.accuracy = accuracy
        self.countryCode = countryCode
        self.countryName = countryName
        self.city = city
        self.isSchengen = isSchengen
        self.source = source
        self.recordedAt = recordedAt
        self.localId = UUID().uuidString
        self.syncStatus = .pending
    }
}

enum LocationSource: String, Codable {
    case manual
    case browser
    case mobileGps = "mobile_gps"
    case calendar
    case photo
    case checkin
}

// MARK: - Sync

enum SyncStatus: String, Codable {
    case pending
    case syncing
    case synced
    case failed
}

struct SyncRequest: Encodable {
    let lastSync: Date?
    let deviceId: String
    let changes: [SyncChange]
}

struct SyncChange: Encodable {
    let type: String
    let action: String
    let localId: String?
    let data: AnyCodable
}

struct SyncResponse: Decodable {
    let success: Bool
    let syncResults: [SyncResult]
    let serverChanges: [ServerChange]
    let conflicts: [SyncConflict]
    let serverTime: Date
}

struct SyncResult: Decodable {
    let localId: String?
    let serverId: Int?
    let success: Bool
    let action: String?
    let error: String?
}

struct ServerChange: Decodable {
    let type: String
    let action: String
    let data: AnyCodable
    let updatedAt: Date
}

struct SyncConflict: Decodable {
    let type: String
    let id: Int
    let localVersion: AnyCodable
    let serverVersion: AnyCodable
}

// MARK: - Passport Control

struct PassportControlData: Decodable {
    let isCompliant: Bool
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: StatusLevel
    let windowStart: Date
    let windowEnd: Date
    let recentTrips: [RecentTripSummary]
    let lastVerified: Date
}

struct RecentTripSummary: Decodable, Identifiable {
    var id: String { "\(country)-\(startDate)" }
    let country: String
    let startDate: Date
    let endDate: Date
    let days: Int
}

enum StatusLevel: String, Decodable {
    case safe
    case warning
    case danger
    case critical
}

// MARK: - App Status

struct AppStatus: Decodable {
    let minVersion: String
    let latestVersion: String
    let forceUpdate: Bool
    let maintenanceMode: Bool
    let maintenanceMessage: String?
    let updateUrl: String?
    let serverTime: Date
    let features: FeatureFlags
}

struct FeatureFlags: Decodable {
    let backgroundGps: Bool
    let photoImport: Bool
    let calendarSync: Bool
    let familyTracking: Bool
    let multiJurisdiction: Bool
}

// MARK: - Device Registration

struct DeviceRegistration: Encodable {
    let deviceId: String
    let pushToken: String
    let platform: String
    let appVersion: String
    let deviceName: String?
}

// MARK: - Family

struct FamilyMember: Codable, Identifiable {
    let id: Int
    let userId: Int
    let name: String
    let relationship: String?
    let nationality: String?
    let passportCountry: String?
    let dateOfBirth: Date?
    let notes: String?
    let color: String
    let isActive: Bool
    let displayOrder: Int
}

// MARK: - Summary

struct JurisdictionSummary: Decodable {
    let jurisdictionId: String
    let jurisdictionName: String
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: StatusLevel
    let periodStart: Date
    let periodEnd: Date
    let lastUpdated: Date
    let notes: String?
}

// MARK: - AnyCodable Helper

struct AnyCodable: Codable {
    let value: Any

    init(_ value: Any) {
        self.value = value
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()

        if let intValue = try? container.decode(Int.self) {
            value = intValue
        } else if let doubleValue = try? container.decode(Double.self) {
            value = doubleValue
        } else if let boolValue = try? container.decode(Bool.self) {
            value = boolValue
        } else if let stringValue = try? container.decode(String.self) {
            value = stringValue
        } else if let arrayValue = try? container.decode([AnyCodable].self) {
            value = arrayValue.map { $0.value }
        } else if let dictValue = try? container.decode([String: AnyCodable].self) {
            value = dictValue.mapValues { $0.value }
        } else {
            value = NSNull()
        }
    }

    func encode(to encoder: Encoder) throws {
        var container = encoder.singleValueContainer()

        switch value {
        case let intValue as Int:
            try container.encode(intValue)
        case let doubleValue as Double:
            try container.encode(doubleValue)
        case let boolValue as Bool:
            try container.encode(boolValue)
        case let stringValue as String:
            try container.encode(stringValue)
        case let arrayValue as [Any]:
            try container.encode(arrayValue.map { AnyCodable($0) })
        case let dictValue as [String: Any]:
            try container.encode(dictValue.mapValues { AnyCodable($0) })
        default:
            try container.encodeNil()
        }
    }
}
