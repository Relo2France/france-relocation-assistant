/**
 * CalendarImportView.swift
 *
 * UI for importing trips from calendar events.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import SwiftUI

struct CalendarImportView: View {
    @StateObject private var viewModel = CalendarImportViewModel()
    @Environment(\.dismiss) private var dismiss

    private let dateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        return formatter
    }()

    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                if !viewModel.hasAccess {
                    accessRequestView
                } else if viewModel.isScanning {
                    scanningView
                } else if viewModel.detectedTrips.isEmpty && viewModel.progress == nil {
                    dateSelectionView
                } else if viewModel.importComplete {
                    importCompleteView
                } else {
                    tripSelectionView
                }
            }
            .navigationTitle("Import from Calendar")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
            }
            .task {
                await viewModel.checkAccess()
            }
            .alert("Error", isPresented: .constant(viewModel.error != nil)) {
                Button("OK") {
                    viewModel.error = nil
                }
            } message: {
                if let error = viewModel.error {
                    Text(error)
                }
            }
        }
    }

    // MARK: - Access Request View

    private var accessRequestView: some View {
        VStack(spacing: 24) {
            Spacer()

            Image(systemName: "calendar")
                .font(.system(size: 60))
                .foregroundColor(.orange)

            Text("Calendar Access")
                .font(.title2.bold())

            Text("Allow access to scan your calendar for travel-related events and automatically detect trips.")
                .multilineTextAlignment(.center)
                .foregroundColor(.secondary)
                .padding(.horizontal, 32)

            Button {
                Task {
                    await viewModel.requestAccess()
                }
            } label: {
                Text("Grant Access")
                    .font(.headline)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.orange)
                    .cornerRadius(12)
            }
            .padding(.horizontal, 32)

            Text("We only read event titles and locations. Your calendar data is not uploaded.")
                .font(.caption)
                .foregroundColor(.secondary)
                .multilineTextAlignment(.center)
                .padding(.horizontal, 24)

            Spacer()
        }
        .padding()
    }

    // MARK: - Date Selection View

    private var dateSelectionView: some View {
        VStack(spacing: 24) {
            Spacer()

            Image(systemName: "calendar.badge.clock")
                .font(.system(size: 50))
                .foregroundColor(.orange)

            Text("Select Date Range")
                .font(.title2.bold())

            Text("Choose the period to scan for travel events.")
                .foregroundColor(.secondary)

            VStack(spacing: 16) {
                DatePicker(
                    "From",
                    selection: $viewModel.startDate,
                    in: ...viewModel.endDate,
                    displayedComponents: .date
                )
                .datePickerStyle(.compact)

                DatePicker(
                    "To",
                    selection: $viewModel.endDate,
                    in: viewModel.startDate...,
                    displayedComponents: .date
                )
                .datePickerStyle(.compact)
            }
            .padding()
            .background(Color(.secondarySystemBackground))
            .cornerRadius(12)
            .padding(.horizontal)

            Button {
                Task {
                    await viewModel.scanEvents()
                }
            } label: {
                HStack {
                    Image(systemName: "magnifyingglass")
                    Text("Scan Calendar")
                }
                .font(.headline)
                .foregroundColor(.white)
                .frame(maxWidth: .infinity)
                .padding()
                .background(Color.orange)
                .cornerRadius(12)
            }
            .padding(.horizontal, 32)

            Spacer()
        }
        .padding()
    }

    // MARK: - Scanning View

    private var scanningView: some View {
        VStack(spacing: 24) {
            Spacer()

            ProgressView()
                .scaleEffect(1.5)

            Text("Scanning calendar events...")
                .font(.headline)

            if let progress = viewModel.progress {
                VStack(spacing: 8) {
                    ProgressView(value: Double(progress.current) / Double(max(1, progress.total)))
                        .progressViewStyle(.linear)

                    Text("\(progress.current) / \(progress.total) events")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                .padding(.horizontal, 48)
            }

            Spacer()
        }
    }

    // MARK: - Trip Selection View

    private var tripSelectionView: some View {
        VStack(spacing: 0) {
            // Header
            HStack {
                Text("\(viewModel.detectedTrips.count) trips found")
                    .font(.headline)

                Spacer()

                Menu {
                    Button("Select All Schengen") {
                        viewModel.selectAllSchengen()
                    }
                    Button("Select All") {
                        viewModel.selectAll()
                    }
                    Button("Deselect All") {
                        viewModel.deselectAll()
                    }
                } label: {
                    Image(systemName: "ellipsis.circle")
                        .font(.title2)
                }
            }
            .padding()

            Divider()

            if viewModel.detectedTrips.isEmpty {
                VStack(spacing: 16) {
                    Spacer()
                    Image(systemName: "calendar.badge.exclamationmark")
                        .font(.system(size: 50))
                        .foregroundColor(.secondary)
                    Text("No travel events found")
                        .font(.headline)
                    Text("Try adjusting the date range or check that your calendar has travel events.")
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                        .multilineTextAlignment(.center)
                        .padding(.horizontal)
                    Spacer()
                }
            } else {
                ScrollView {
                    LazyVStack(spacing: 12) {
                        ForEach(viewModel.detectedTrips) { trip in
                            CalendarTripCard(
                                trip: trip,
                                isSelected: viewModel.selectedTrips.contains(trip.id),
                                dateFormatter: dateFormatter
                            ) {
                                viewModel.toggleTrip(trip)
                            }
                        }
                    }
                    .padding()
                }
            }

            Divider()

            // Import button
            VStack(spacing: 8) {
                let selectedCount = viewModel.selectedTrips.count
                let schengenDays = viewModel.detectedTrips
                    .filter { viewModel.selectedTrips.contains($0.id) && $0.isSchengen }
                    .reduce(0) { $0 + $1.durationDays }

                if schengenDays > 0 {
                    Text("\(schengenDays) Schengen days will be imported")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }

                Button {
                    Task {
                        await viewModel.importSelected()
                    }
                } label: {
                    Text("Import \(selectedCount) Trip\(selectedCount == 1 ? "" : "s")")
                        .font(.headline)
                        .foregroundColor(.white)
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(selectedCount > 0 ? Color.orange : Color.gray)
                        .cornerRadius(12)
                }
                .disabled(selectedCount == 0)
            }
            .padding()
        }
    }

    // MARK: - Import Complete View

    private var importCompleteView: some View {
        VStack(spacing: 24) {
            Spacer()

            Image(systemName: "checkmark.circle.fill")
                .font(.system(size: 60))
                .foregroundColor(.green)

            Text("Import Complete!")
                .font(.title2.bold())

            Text("\(viewModel.selectedTrips.count) trips imported successfully.")
                .foregroundColor(.secondary)

            Button {
                dismiss()
            } label: {
                Text("Done")
                    .font(.headline)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.orange)
                    .cornerRadius(12)
            }
            .padding(.horizontal, 32)

            Spacer()
        }
        .padding()
    }
}

// MARK: - Calendar Trip Card

struct CalendarTripCard: View {
    let trip: DetectedCalendarTrip
    let isSelected: Bool
    let dateFormatter: DateFormatter
    let onTap: () -> Void

    var body: some View {
        Button(action: onTap) {
            HStack(spacing: 12) {
                // Checkbox
                Image(systemName: isSelected ? "checkmark.circle.fill" : "circle")
                    .font(.title2)
                    .foregroundColor(isSelected ? .orange : .gray)

                // Calendar icon
                RoundedRectangle(cornerRadius: 8)
                    .fill(Color.orange.opacity(0.15))
                    .frame(width: 50, height: 50)
                    .overlay(
                        Image(systemName: "calendar")
                            .foregroundColor(.orange)
                    )

                // Trip info
                VStack(alignment: .leading, spacing: 4) {
                    HStack {
                        Text(SchengenCountries.flagFor(code: trip.countryCode))
                        Text(trip.country)
                            .font(.headline)
                    }

                    Text(trip.eventTitle)
                        .font(.caption)
                        .foregroundColor(.primary)
                        .lineLimit(1)

                    Text("\(dateFormatter.string(from: trip.startDate)) - \(dateFormatter.string(from: trip.endDate))")
                        .font(.caption)
                        .foregroundColor(.secondary)

                    Text(trip.calendarName)
                        .font(.caption2)
                        .foregroundColor(.secondary)
                }

                Spacer()

                // Duration and Schengen badge
                VStack(alignment: .trailing, spacing: 4) {
                    Text("\(trip.durationDays)d")
                        .font(.title3.bold())
                        .foregroundColor(trip.isSchengen ? .orange : .secondary)

                    if trip.isSchengen {
                        Text("SCHENGEN")
                            .font(.caption2.bold())
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Color.orange.opacity(0.15))
                            .foregroundColor(.orange)
                            .cornerRadius(4)
                    }
                }
            }
            .padding()
            .background(Color(.secondarySystemBackground))
            .cornerRadius(12)
            .overlay(
                RoundedRectangle(cornerRadius: 12)
                    .stroke(isSelected ? Color.orange : Color.clear, lineWidth: 2)
            )
        }
        .buttonStyle(.plain)
    }
}

#Preview {
    CalendarImportView()
}
