/**
 * MyTravelStatusWidget.swift
 *
 * Home screen widget for MyTravelStatus iOS app.
 * Displays compliance status for tracked jurisdictions.
 *
 * Supports three widget sizes:
 * - Small: Single jurisdiction with progress ring
 * - Medium: Primary jurisdiction with detailed stats
 * - Large: Multi-jurisdiction overview grid
 *
 * @package MyTravelStatus
 * @since   1.8.3
 */

import WidgetKit
import SwiftUI

// MARK: - Widget Entry

struct ComplianceEntry: TimelineEntry {
    let date: Date
    let configuration: ConfigurationIntent?
    let jurisdictions: [WidgetJurisdiction]
    let isPlaceholder: Bool
    let error: String?

    static var placeholder: ComplianceEntry {
        ComplianceEntry(
            date: Date(),
            configuration: nil,
            jurisdictions: [
                WidgetJurisdiction(
                    code: "schengen",
                    name: "Schengen Zone",
                    flagEmoji: "🇪🇺",
                    daysUsed: 45,
                    daysAllowed: 90,
                    daysRemaining: 45,
                    status: .ok,
                    category: "visa"
                )
            ],
            isPlaceholder: true,
            error: nil
        )
    }

    static var preview: ComplianceEntry {
        ComplianceEntry(
            date: Date(),
            configuration: nil,
            jurisdictions: [
                WidgetJurisdiction(
                    code: "schengen",
                    name: "Schengen Zone",
                    flagEmoji: "🇪🇺",
                    daysUsed: 67,
                    daysAllowed: 90,
                    daysRemaining: 23,
                    status: .warning,
                    category: "visa"
                ),
                WidgetJurisdiction(
                    code: "uk_srt",
                    name: "UK SRT",
                    flagEmoji: "🇬🇧",
                    daysUsed: 45,
                    daysAllowed: 183,
                    daysRemaining: 138,
                    status: .ok,
                    category: "tax"
                ),
                WidgetJurisdiction(
                    code: "us_spt",
                    name: "US SPT",
                    flagEmoji: "🇺🇸",
                    daysUsed: 120,
                    daysAllowed: 183,
                    daysRemaining: 63,
                    status: .ok,
                    category: "tax"
                ),
                WidgetJurisdiction(
                    code: "ie_tax",
                    name: "Ireland 183",
                    flagEmoji: "🇮🇪",
                    daysUsed: 12,
                    daysAllowed: 183,
                    daysRemaining: 171,
                    status: .ok,
                    category: "tax"
                )
            ],
            isPlaceholder: false,
            error: nil
        )
    }
}

// MARK: - Widget Jurisdiction Model

struct WidgetJurisdiction: Identifiable {
    let id = UUID()
    let code: String
    let name: String
    let flagEmoji: String?
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: ComplianceStatus
    let category: String

    var percentage: Double {
        guard daysAllowed > 0 else { return 0 }
        return min(1.0, Double(daysUsed) / Double(daysAllowed))
    }

    var shortName: String {
        // Return abbreviated name for small widgets
        switch code {
        case "schengen": return "Schengen"
        case "uk_srt": return "UK SRT"
        case "us_spt": return "US SPT"
        case "ie_tax": return "Ireland"
        case "fr_tax": return "France"
        default: return String(name.prefix(10))
        }
    }
}

enum ComplianceStatus: String {
    case ok
    case warning
    case critical
    case exceeded

    var color: Color {
        switch self {
        case .ok: return .green
        case .warning: return .yellow
        case .critical: return .orange
        case .exceeded: return .red
        }
    }

    var systemImage: String {
        switch self {
        case .ok: return "checkmark.circle.fill"
        case .warning: return "exclamationmark.triangle.fill"
        case .critical: return "exclamationmark.circle.fill"
        case .exceeded: return "xmark.circle.fill"
        }
    }
}

// MARK: - Configuration Intent

struct ConfigurationIntent {
    var primaryJurisdiction: String?
}

// MARK: - Timeline Provider

struct ComplianceProvider: TimelineProvider {
    typealias Entry = ComplianceEntry

    func placeholder(in context: Context) -> ComplianceEntry {
        ComplianceEntry.placeholder
    }

    func getSnapshot(in context: Context, completion: @escaping (ComplianceEntry) -> Void) {
        if context.isPreview {
            completion(ComplianceEntry.preview)
        } else {
            fetchComplianceData { entry in
                completion(entry ?? ComplianceEntry.placeholder)
            }
        }
    }

    func getTimeline(in context: Context, completion: @escaping (Timeline<ComplianceEntry>) -> Void) {
        fetchComplianceData { entry in
            let currentEntry = entry ?? ComplianceEntry(
                date: Date(),
                configuration: nil,
                jurisdictions: [],
                isPlaceholder: false,
                error: "Unable to load data"
            )

            // Refresh every 30 minutes
            let refreshDate = Calendar.current.date(byAdding: .minute, value: 30, to: Date()) ?? Date()
            let timeline = Timeline(entries: [currentEntry], policy: .after(refreshDate))
            completion(timeline)
        }
    }

    private func fetchComplianceData(completion: @escaping (ComplianceEntry?) -> Void) {
        // Try to load from shared UserDefaults (App Group)
        let sharedDefaults = UserDefaults(suiteName: "group.com.mytravelstatus.app")

        if let data = sharedDefaults?.data(forKey: "widgetComplianceData"),
           let cached = try? JSONDecoder().decode(CachedComplianceData.self, from: data) {

            // Check if cache is still valid (less than 1 hour old)
            if Date().timeIntervalSince(cached.timestamp) < 3600 {
                let jurisdictions = cached.jurisdictions.map { j in
                    WidgetJurisdiction(
                        code: j.code,
                        name: j.name,
                        flagEmoji: j.flagEmoji,
                        daysUsed: j.daysUsed,
                        daysAllowed: j.daysAllowed,
                        daysRemaining: j.daysRemaining,
                        status: ComplianceStatus(rawValue: j.status) ?? .ok,
                        category: j.category
                    )
                }

                completion(ComplianceEntry(
                    date: Date(),
                    configuration: nil,
                    jurisdictions: jurisdictions,
                    isPlaceholder: false,
                    error: nil
                ))
                return
            }
        }

        // If no cache or expired, try API fetch
        fetchFromAPI(completion: completion)
    }

    private func fetchFromAPI(completion: @escaping (ComplianceEntry?) -> Void) {
        let sharedDefaults = UserDefaults(suiteName: "group.com.mytravelstatus.app")

        guard let baseURL = sharedDefaults?.string(forKey: "apiBaseURL"),
              let authToken = sharedDefaults?.string(forKey: "authToken") else {
            completion(nil)
            return
        }

        guard let url = URL(string: "\(baseURL)/wp-json/mts/v1/jurisdictions/summary") else {
            completion(nil)
            return
        }

        var request = URLRequest(url: url)
        request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
        request.timeoutInterval = 10

        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data, error == nil else {
                completion(nil)
                return
            }

            // Parse API response
            if let summaries = try? JSONDecoder().decode([String: APISummary].self, from: data) {
                let jurisdictions = summaries.map { (code, summary) in
                    WidgetJurisdiction(
                        code: code,
                        name: summary.jurisdictionName,
                        flagEmoji: summary.flagEmoji,
                        daysUsed: summary.daysUsed,
                        daysAllowed: summary.daysAllowed,
                        daysRemaining: summary.daysRemaining,
                        status: ComplianceStatus(rawValue: summary.status) ?? .ok,
                        category: summary.category ?? "visa"
                    )
                }

                // Cache the data
                let cachedData = CachedComplianceData(
                    timestamp: Date(),
                    jurisdictions: jurisdictions.map { CachedJurisdiction(from: $0) }
                )
                if let encoded = try? JSONEncoder().encode(cachedData) {
                    sharedDefaults?.set(encoded, forKey: "widgetComplianceData")
                }

                completion(ComplianceEntry(
                    date: Date(),
                    configuration: nil,
                    jurisdictions: jurisdictions,
                    isPlaceholder: false,
                    error: nil
                ))
            } else {
                completion(nil)
            }
        }.resume()
    }
}

// MARK: - API Response Models

struct APISummary: Decodable {
    let jurisdictionCode: String
    let jurisdictionName: String
    let category: String?
    let flagEmoji: String?
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: String
}

// MARK: - Cache Models

struct CachedComplianceData: Codable {
    let timestamp: Date
    let jurisdictions: [CachedJurisdiction]
}

struct CachedJurisdiction: Codable {
    let code: String
    let name: String
    let flagEmoji: String?
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: String
    let category: String

    init(from jurisdiction: WidgetJurisdiction) {
        self.code = jurisdiction.code
        self.name = jurisdiction.name
        self.flagEmoji = jurisdiction.flagEmoji
        self.daysUsed = jurisdiction.daysUsed
        self.daysAllowed = jurisdiction.daysAllowed
        self.daysRemaining = jurisdiction.daysRemaining
        self.status = jurisdiction.status.rawValue
        self.category = jurisdiction.category
    }
}

// MARK: - Widget Views

struct SmallWidgetView: View {
    let entry: ComplianceEntry

    var primaryJurisdiction: WidgetJurisdiction? {
        entry.jurisdictions.first
    }

    var body: some View {
        if let jurisdiction = primaryJurisdiction {
            VStack(spacing: 8) {
                // Header with flag and name
                HStack(spacing: 4) {
                    if let flag = jurisdiction.flagEmoji {
                        Text(flag)
                            .font(.title3)
                    }
                    Text(jurisdiction.shortName)
                        .font(.caption)
                        .fontWeight(.semibold)
                        .lineLimit(1)
                }

                // Progress ring
                ZStack {
                    Circle()
                        .stroke(Color.gray.opacity(0.2), lineWidth: 8)

                    Circle()
                        .trim(from: 0, to: jurisdiction.percentage)
                        .stroke(jurisdiction.status.color, style: StrokeStyle(lineWidth: 8, lineCap: .round))
                        .rotationEffect(.degrees(-90))

                    VStack(spacing: 0) {
                        Text("\(jurisdiction.daysUsed)")
                            .font(.title2)
                            .fontWeight(.bold)
                        Text("/\(jurisdiction.daysAllowed)")
                            .font(.caption2)
                            .foregroundColor(.secondary)
                    }
                }
                .frame(width: 70, height: 70)

                // Days remaining
                Text("\(jurisdiction.daysRemaining) days left")
                    .font(.caption2)
                    .foregroundColor(.secondary)
            }
            .padding()
        } else if let error = entry.error {
            VStack {
                Image(systemName: "exclamationmark.triangle")
                    .font(.title)
                    .foregroundColor(.orange)
                Text(error)
                    .font(.caption2)
                    .multilineTextAlignment(.center)
            }
            .padding()
        } else {
            VStack {
                Image(systemName: "airplane")
                    .font(.title)
                Text("No Data")
                    .font(.caption)
            }
            .padding()
        }
    }
}

struct MediumWidgetView: View {
    let entry: ComplianceEntry

    var body: some View {
        HStack(spacing: 16) {
            // Primary jurisdiction with ring
            if let primary = entry.jurisdictions.first {
                VStack(spacing: 8) {
                    HStack(spacing: 4) {
                        if let flag = primary.flagEmoji {
                            Text(flag)
                                .font(.title2)
                        }
                        Text(primary.shortName)
                            .font(.headline)
                            .lineLimit(1)
                    }

                    ZStack {
                        Circle()
                            .stroke(Color.gray.opacity(0.2), lineWidth: 10)

                        Circle()
                            .trim(from: 0, to: primary.percentage)
                            .stroke(primary.status.color, style: StrokeStyle(lineWidth: 10, lineCap: .round))
                            .rotationEffect(.degrees(-90))

                        VStack(spacing: 0) {
                            Text("\(primary.daysUsed)")
                                .font(.title)
                                .fontWeight(.bold)
                            Text("/\(primary.daysAllowed)")
                                .font(.caption)
                                .foregroundColor(.secondary)
                        }
                    }
                    .frame(width: 90, height: 90)

                    HStack(spacing: 4) {
                        Image(systemName: primary.status.systemImage)
                            .foregroundColor(primary.status.color)
                        Text("\(primary.daysRemaining) days left")
                            .font(.caption)
                    }
                }
            }

            Divider()

            // Other jurisdictions list
            VStack(alignment: .leading, spacing: 6) {
                Text("Other Rules")
                    .font(.caption)
                    .foregroundColor(.secondary)

                ForEach(Array(entry.jurisdictions.dropFirst().prefix(3))) { jurisdiction in
                    HStack {
                        if let flag = jurisdiction.flagEmoji {
                            Text(flag)
                                .font(.caption)
                        }
                        Text(jurisdiction.shortName)
                            .font(.caption2)
                            .lineLimit(1)
                        Spacer()
                        Text("\(jurisdiction.daysUsed)/\(jurisdiction.daysAllowed)")
                            .font(.caption2)
                            .foregroundColor(jurisdiction.status.color)
                    }
                }

                if entry.jurisdictions.count <= 1 {
                    Text("Add more rules in app")
                        .font(.caption2)
                        .foregroundColor(.secondary)
                }
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding()
    }
}

struct LargeWidgetView: View {
    let entry: ComplianceEntry

    let columns = [
        GridItem(.flexible()),
        GridItem(.flexible())
    ]

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            // Header
            HStack {
                Image(systemName: "airplane")
                    .foregroundColor(.accentColor)
                Text("Travel Compliance")
                    .font(.headline)
                Spacer()
                Text("MyTravelStatus")
                    .font(.caption2)
                    .foregroundColor(.secondary)
            }

            if entry.jurisdictions.isEmpty {
                Spacer()
                VStack(spacing: 8) {
                    Image(systemName: "globe")
                        .font(.largeTitle)
                        .foregroundColor(.secondary)
                    Text("No jurisdictions tracked")
                        .font(.subheadline)
                    Text("Open app to add rules")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                .frame(maxWidth: .infinity)
                Spacer()
            } else {
                // Jurisdiction grid
                LazyVGrid(columns: columns, spacing: 12) {
                    ForEach(entry.jurisdictions.prefix(4)) { jurisdiction in
                        JurisdictionCard(jurisdiction: jurisdiction)
                    }
                }

                if entry.jurisdictions.count > 4 {
                    Text("+\(entry.jurisdictions.count - 4) more in app")
                        .font(.caption2)
                        .foregroundColor(.secondary)
                        .frame(maxWidth: .infinity, alignment: .trailing)
                }
            }
        }
        .padding()
    }
}

struct JurisdictionCard: View {
    let jurisdiction: WidgetJurisdiction

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            // Header
            HStack(spacing: 4) {
                if let flag = jurisdiction.flagEmoji {
                    Text(flag)
                        .font(.caption)
                }
                Text(jurisdiction.shortName)
                    .font(.caption)
                    .fontWeight(.medium)
                    .lineLimit(1)
                Spacer()
                Image(systemName: jurisdiction.status.systemImage)
                    .font(.caption2)
                    .foregroundColor(jurisdiction.status.color)
            }

            // Progress bar
            GeometryReader { geo in
                ZStack(alignment: .leading) {
                    RoundedRectangle(cornerRadius: 4)
                        .fill(Color.gray.opacity(0.2))

                    RoundedRectangle(cornerRadius: 4)
                        .fill(jurisdiction.status.color)
                        .frame(width: geo.size.width * jurisdiction.percentage)
                }
            }
            .frame(height: 8)

            // Stats
            HStack {
                Text("\(jurisdiction.daysUsed)/\(jurisdiction.daysAllowed)")
                    .font(.caption2)
                    .fontWeight(.semibold)
                Spacer()
                Text("\(jurisdiction.daysRemaining) left")
                    .font(.caption2)
                    .foregroundColor(.secondary)
            }
        }
        .padding(10)
        .background(Color.gray.opacity(0.1))
        .cornerRadius(10)
    }
}

// MARK: - Main Widget

@main
struct MyTravelStatusWidget: Widget {
    let kind: String = "MyTravelStatusWidget"

    var body: some WidgetConfiguration {
        StaticConfiguration(kind: kind, provider: ComplianceProvider()) { entry in
            WidgetEntryView(entry: entry)
        }
        .configurationDisplayName("Travel Compliance")
        .description("Track your visa and tax residency days at a glance.")
        .supportedFamilies([.systemSmall, .systemMedium, .systemLarge])
    }
}

struct WidgetEntryView: View {
    @Environment(\.widgetFamily) var family
    let entry: ComplianceEntry

    var body: some View {
        switch family {
        case .systemSmall:
            SmallWidgetView(entry: entry)
        case .systemMedium:
            MediumWidgetView(entry: entry)
        case .systemLarge:
            LargeWidgetView(entry: entry)
        default:
            SmallWidgetView(entry: entry)
        }
    }
}

// MARK: - Previews

struct MyTravelStatusWidget_Previews: PreviewProvider {
    static var previews: some View {
        Group {
            WidgetEntryView(entry: ComplianceEntry.preview)
                .previewContext(WidgetPreviewContext(family: .systemSmall))
                .previewDisplayName("Small")

            WidgetEntryView(entry: ComplianceEntry.preview)
                .previewContext(WidgetPreviewContext(family: .systemMedium))
                .previewDisplayName("Medium")

            WidgetEntryView(entry: ComplianceEntry.preview)
                .previewContext(WidgetPreviewContext(family: .systemLarge))
                .previewDisplayName("Large")
        }
    }
}
