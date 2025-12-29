/**
 * KeychainHelper.swift
 *
 * Secure storage for sensitive data like auth tokens.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation
import Security

struct KeychainHelper {
    private static let serviceName = "com.relo2france.schengen"

    /// Save a string value to keychain
    static func save(key: String, value: String) {
        guard let data = value.data(using: .utf8) else { return }

        // Delete any existing value
        delete(key: key)

        // Add new value
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: serviceName,
            kSecAttrAccount as String: key,
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlock
        ]

        let status = SecItemAdd(query as CFDictionary, nil)
        if status != errSecSuccess {
            print("Keychain save failed: \(status)")
        }
    }

    /// Load a string value from keychain
    static func load(key: String) -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: serviceName,
            kSecAttrAccount as String: key,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]

        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)

        guard status == errSecSuccess,
              let data = result as? Data,
              let string = String(data: data, encoding: .utf8) else {
            return nil
        }

        return string
    }

    /// Delete a value from keychain
    static func delete(key: String) {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: serviceName,
            kSecAttrAccount as String: key
        ]

        SecItemDelete(query as CFDictionary)
    }

    /// Delete all keychain items for this app
    static func clearAll() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: serviceName
        ]

        SecItemDelete(query as CFDictionary)
    }
}
