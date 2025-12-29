/**
 * PhotoImportView.swift
 *
 * UI for importing trips from photo library GPS metadata.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import SwiftUI

struct PhotoImportView: View {
    @StateObject private var viewModel = PhotoImportViewModel()
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
            .navigationTitle("Import from Photos")
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

            Image(systemName: "photo.on.rectangle.angled")
                .font(.system(size: 60))
                .foregroundColor(.blue)

            Text("Photo Library Access")
                .font(.title2.bold())

            Text("Allow access to scan your photos for GPS data and automatically detect your travel history.")
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
                    .background(Color.blue)
                    .cornerRadius(12)
            }
            .padding(.horizontal, 32)

            Text("We only read GPS metadata. Your photos stay on your device.")
                .font(.caption)
                .foregroundColor(.secondary)

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
                .foregroundColor(.blue)

            Text("Select Date Range")
                .font(.title2.bold())

            Text("Choose the period to scan for travel photos.")
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
                    await viewModel.scanPhotos()
                }
            } label: {
                HStack {
                    Image(systemName: "magnifyingglass")
                    Text("Scan Photos")
                }
                .font(.headline)
                .foregroundColor(.white)
                .frame(maxWidth: .infinity)
                .padding()
                .background(Color.blue)
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

            Text(scanningPhaseText)
                .font(.headline)

            if let progress = viewModel.progress {
                VStack(spacing: 8) {
                    ProgressView(value: progress.percentage)
                        .progressViewStyle(.linear)

                    Text("\(progress.current) / \(progress.total)")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                .padding(.horizontal, 48)
            }

            Spacer()
        }
    }

    private var scanningPhaseText: String {
        guard let progress = viewModel.progress else { return "Scanning..." }

        switch progress.phase {
        case .scanning:
            return "Scanning photos..."
        case .geocoding:
            return "Detecting countries..."
        case .grouping:
            return "Grouping into trips..."
        case .complete:
            return "Complete!"
        }
    }

    // MARK: - Trip Selection View

    private var tripSelectionView: some View {
        VStack(spacing: 0) {
            // Header with selection controls
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

            // Trip list
            ScrollView {
                LazyVStack(spacing: 12) {
                    ForEach(viewModel.detectedTrips) { trip in
                        TripSelectionCard(
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
                        .background(selectedCount > 0 ? Color.blue : Color.gray)
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
                    .background(Color.blue)
                    .cornerRadius(12)
            }
            .padding(.horizontal, 32)

            Spacer()
        }
        .padding()
    }
}

// MARK: - Trip Selection Card

struct TripSelectionCard: View {
    let trip: DetectedPhotoTrip
    let isSelected: Bool
    let dateFormatter: DateFormatter
    let onTap: () -> Void

    var body: some View {
        Button(action: onTap) {
            HStack(spacing: 12) {
                // Checkbox
                Image(systemName: isSelected ? "checkmark.circle.fill" : "circle")
                    .font(.title2)
                    .foregroundColor(isSelected ? .blue : .gray)

                // Photo thumbnail
                if let photo = trip.samplePhoto {
                    Image(uiImage: photo)
                        .resizable()
                        .aspectRatio(contentMode: .fill)
                        .frame(width: 50, height: 50)
                        .cornerRadius(8)
                } else {
                    RoundedRectangle(cornerRadius: 8)
                        .fill(Color.gray.opacity(0.3))
                        .frame(width: 50, height: 50)
                        .overlay(
                            Image(systemName: "photo")
                                .foregroundColor(.gray)
                        )
                }

                // Trip info
                VStack(alignment: .leading, spacing: 4) {
                    HStack {
                        Text(SchengenCountries.flagFor(code: trip.countryCode))
                        Text(trip.country)
                            .font(.headline)
                    }

                    Text("\(dateFormatter.string(from: trip.startDate)) - \(dateFormatter.string(from: trip.endDate))")
                        .font(.caption)
                        .foregroundColor(.secondary)

                    Text("\(trip.photoCount) photos")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }

                Spacer()

                // Duration and Schengen badge
                VStack(alignment: .trailing, spacing: 4) {
                    Text("\(trip.durationDays)d")
                        .font(.title3.bold())
                        .foregroundColor(trip.isSchengen ? .blue : .secondary)

                    if trip.isSchengen {
                        Text("SCHENGEN")
                            .font(.caption2.bold())
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Color.blue.opacity(0.15))
                            .foregroundColor(.blue)
                            .cornerRadius(4)
                    }
                }
            }
            .padding()
            .background(Color(.secondarySystemBackground))
            .cornerRadius(12)
            .overlay(
                RoundedRectangle(cornerRadius: 12)
                    .stroke(isSelected ? Color.blue : Color.clear, lineWidth: 2)
            )
        }
        .buttonStyle(.plain)
    }
}

#Preview {
    PhotoImportView()
}
