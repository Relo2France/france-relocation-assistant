/**
 * APIClient.swift
 *
 * HTTP client for communicating with the WordPress REST API.
 * Handles authentication, request/response encoding, and error handling.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation

actor APIClient {
    static let shared = APIClient()

    // MARK: - Configuration

    private let baseURL: URL
    private var authToken: String?
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder
    private let session: URLSession

    /// App version for status checks
    private let appVersion = "1.0.0"

    // MARK: - Initialization

    private init() {
        // TODO: Configure from environment or settings
        self.baseURL = URL(string: "https://your-site.com/wp-json/r2f-schengen/v1")!

        // Configure decoder
        decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        decoder.dateDecodingStrategy = .iso8601

        // Configure encoder
        encoder = JSONEncoder()
        encoder.keyEncodingStrategy = .convertToSnakeCase
        encoder.dateEncodingStrategy = .iso8601

        // Configure session
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 30
        config.timeoutIntervalForResource = 60
        session = URLSession(configuration: config)

        // Load saved auth token
        authToken = KeychainHelper.load(key: "authToken")
    }

    // MARK: - Authentication

    func login(email: String, password: String) async throws {
        let loginURL = baseURL.deletingLastPathComponent().appendingPathComponent("jwt-auth/v1/token")

        var request = URLRequest(url: loginURL)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")

        let body = ["username": email, "password": password]
        request.httpBody = try encoder.encode(body)

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        struct TokenResponse: Decodable {
            let token: String
        }

        let tokenResponse = try decoder.decode(TokenResponse.self, from: data)
        authToken = tokenResponse.token
        KeychainHelper.save(key: "authToken", value: tokenResponse.token)
    }

    func logout() {
        authToken = nil
        KeychainHelper.delete(key: "authToken")
    }

    var isAuthenticated: Bool {
        authToken != nil
    }

    // MARK: - App Status

    func getAppStatus() async throws -> AppStatus {
        var components = URLComponents(url: baseURL.appendingPathComponent("app/status"), resolvingAgainstBaseURL: true)!
        components.queryItems = [
            URLQueryItem(name: "version", value: appVersion),
            URLQueryItem(name: "platform", value: "ios")
        ]

        let request = URLRequest(url: components.url!)
        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(AppStatus.self, from: data)
    }

    // MARK: - Passport Control

    func getPassportControlData() async throws -> PassportControlData {
        let request = try authenticatedRequest(path: "passport-control", method: "GET")
        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(PassportControlData.self, from: data)
    }

    // MARK: - Trips

    func getTrips() async throws -> [Trip] {
        let request = try authenticatedRequest(path: "trips", method: "GET")
        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode([Trip].self, from: data)
    }

    func createTrip(_ trip: Trip) async throws -> Trip {
        var request = try authenticatedRequest(path: "trips", method: "POST")
        request.httpBody = try encoder.encode(trip)

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(Trip.self, from: data)
    }

    func updateTrip(_ trip: Trip) async throws -> Trip {
        var request = try authenticatedRequest(path: "trips/\(trip.id)", method: "PUT")
        request.httpBody = try encoder.encode(trip)

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(Trip.self, from: data)
    }

    func deleteTrip(id: Int) async throws {
        let request = try authenticatedRequest(path: "trips/\(id)", method: "DELETE")
        let (_, response) = try await session.data(for: request)
        try validateResponse(response)
    }

    // MARK: - Sync

    func sync(request syncRequest: SyncRequest) async throws -> SyncResponse {
        var request = try authenticatedRequest(path: "sync", method: "POST")
        request.httpBody = try encoder.encode(syncRequest)

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(SyncResponse.self, from: data)
    }

    func getChanges(since: Date) async throws -> [ServerChange] {
        var components = URLComponents(url: baseURL.appendingPathComponent("changes"), resolvingAgainstBaseURL: true)!

        let formatter = ISO8601DateFormatter()
        components.queryItems = [
            URLQueryItem(name: "since", value: formatter.string(from: since))
        ]

        var request = URLRequest(url: components.url!)
        try addAuthHeader(to: &request)

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        struct ChangesResponse: Decodable {
            let changes: [ServerChange]
        }

        let changesResponse = try decoder.decode(ChangesResponse.self, from: data)
        return changesResponse.changes
    }

    // MARK: - Locations

    func uploadLocations(_ locations: [LocationReading]) async throws -> BatchUploadResult {
        var request = try authenticatedRequest(path: "locations/batch", method: "POST")

        struct BatchRequest: Encodable {
            let locations: [LocationReading]
        }

        request.httpBody = try encoder.encode(BatchRequest(locations: locations))

        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(BatchUploadResult.self, from: data)
    }

    // MARK: - Device Registration

    func registerDevice(_ registration: DeviceRegistration) async throws {
        var request = try authenticatedRequest(path: "device/register", method: "POST")
        request.httpBody = try encoder.encode(registration)

        let (_, response) = try await session.data(for: request)
        try validateResponse(response)
    }

    func unregisterDevice(deviceId: String) async throws {
        var request = try authenticatedRequest(path: "device/unregister", method: "POST")

        struct UnregisterRequest: Encodable {
            let deviceId: String
        }

        request.httpBody = try encoder.encode(UnregisterRequest(deviceId: deviceId))

        let (_, response) = try await session.data(for: request)
        try validateResponse(response)
    }

    // MARK: - Summary

    func getSummary() async throws -> JurisdictionSummary {
        let request = try authenticatedRequest(path: "summary", method: "GET")
        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode(JurisdictionSummary.self, from: data)
    }

    // MARK: - Family

    func getFamilyMembers() async throws -> [FamilyMember] {
        let request = try authenticatedRequest(path: "family", method: "GET")
        let (data, response) = try await session.data(for: request)
        try validateResponse(response)

        return try decoder.decode([FamilyMember].self, from: data)
    }

    // MARK: - Private Helpers

    private func authenticatedRequest(path: String, method: String) throws -> URLRequest {
        guard let token = authToken else {
            throw APIError.notAuthenticated
        }

        var request = URLRequest(url: baseURL.appendingPathComponent(path))
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

        return request
    }

    private func addAuthHeader(to request: inout URLRequest) throws {
        guard let token = authToken else {
            throw APIError.notAuthenticated
        }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
    }

    private func validateResponse(_ response: URLResponse) throws {
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        switch httpResponse.statusCode {
        case 200...299:
            return
        case 401:
            throw APIError.notAuthenticated
        case 403:
            throw APIError.forbidden
        case 404:
            throw APIError.notFound
        case 409:
            throw APIError.conflict
        case 500...599:
            throw APIError.serverError(httpResponse.statusCode)
        default:
            throw APIError.httpError(httpResponse.statusCode)
        }
    }
}

// MARK: - API Errors

enum APIError: Error, LocalizedError {
    case notAuthenticated
    case forbidden
    case notFound
    case conflict
    case invalidResponse
    case serverError(Int)
    case httpError(Int)

    var errorDescription: String? {
        switch self {
        case .notAuthenticated:
            return "Please log in to continue"
        case .forbidden:
            return "You don't have permission to access this resource"
        case .notFound:
            return "The requested resource was not found"
        case .conflict:
            return "There was a sync conflict"
        case .invalidResponse:
            return "Invalid response from server"
        case .serverError(let code):
            return "Server error (\(code))"
        case .httpError(let code):
            return "Request failed (\(code))"
        }
    }
}

// MARK: - Response Types

struct BatchUploadResult: Decodable {
    let success: Bool
    let inserted: Int
    let total: Int
    let errors: [BatchError]?

    struct BatchError: Decodable {
        let index: Int
        let error: String
    }
}
