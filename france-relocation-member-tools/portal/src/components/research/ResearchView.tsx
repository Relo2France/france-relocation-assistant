/**
 * ResearchView Component
 *
 * Main view for the France Research Tool.
 * Allows users to explore regions, departments, and communes of France
 * and generate AI-researched relocation reports.
 */

import { useState, useCallback } from 'react';
import { MapPin, ChevronRight, FileText } from 'lucide-react';
import FranceMap from './FranceMap';
import RegionView from './RegionView';
import DepartmentView from './DepartmentView';
import LocationSearch from './LocationSearch';
import GenerateReportModal from './GenerateReportModal';
import type { ResearchLevel, LocationBreadcrumb, FranceRegion, FranceDepartment, FranceCommune } from '@/types';

type ViewLevel = 'france' | 'region' | 'department' | 'commune';

interface SelectedLocation {
  level: ViewLevel;
  region?: FranceRegion;
  department?: FranceDepartment;
  commune?: FranceCommune;
}

export default function ResearchView() {
  const [selectedLocation, setSelectedLocation] = useState<SelectedLocation>({ level: 'france' });
  const [breadcrumbs, setBreadcrumbs] = useState<LocationBreadcrumb[]>([]);
  const [showReportModal, setShowReportModal] = useState(false);
  const [reportLocation, setReportLocation] = useState<{
    type: ResearchLevel;
    code: string;
    name: string;
  } | null>(null);

  // Handle region selection from France map
  const handleRegionSelect = useCallback((region: FranceRegion) => {
    setSelectedLocation({ level: 'region', region });
    setBreadcrumbs([
      { level: 'region', code: region.code, name: region.name },
    ]);
  }, []);

  // Handle department selection from region view
  const handleDepartmentSelect = useCallback((department: FranceDepartment) => {
    setSelectedLocation((prev) => ({
      ...prev,
      level: 'department',
      department,
    }));
    setBreadcrumbs((prev) => [
      ...prev.slice(0, 1),
      { level: 'department', code: department.code, name: department.name },
    ]);
  }, []);

  // Handle commune selection from department view
  const handleCommuneSelect = useCallback((commune: FranceCommune) => {
    setSelectedLocation((prev) => ({
      ...prev,
      level: 'commune',
      commune,
    }));
    setBreadcrumbs((prev) => [
      ...prev.slice(0, 2),
      { level: 'commune', code: commune.code, name: commune.name },
    ]);
  }, []);

  // Handle breadcrumb navigation
  const handleBreadcrumbClick = useCallback((index: number) => {
    if (index === -1) {
      // Back to France overview
      setSelectedLocation({ level: 'france' });
      setBreadcrumbs([]);
    } else {
      const crumb = breadcrumbs[index];
      const newBreadcrumbs = breadcrumbs.slice(0, index + 1);
      setBreadcrumbs(newBreadcrumbs);

      if (crumb.level === 'region') {
        setSelectedLocation((prev) => ({
          level: 'region',
          region: prev.region,
        }));
      } else if (crumb.level === 'department') {
        setSelectedLocation((prev) => ({
          level: 'department',
          region: prev.region,
          department: prev.department,
        }));
      }
    }
  }, [breadcrumbs]);

  // Open report modal for current location
  const handleGenerateReport = useCallback((type: ResearchLevel, code: string, name: string) => {
    setReportLocation({ type, code, name });
    setShowReportModal(true);
  }, []);

  // Handle search result selection
  const handleSearchSelect = useCallback((result: FranceRegion | FranceDepartment | FranceCommune, type: ResearchLevel) => {
    if (type === 'region') {
      handleRegionSelect(result as FranceRegion);
    } else if (type === 'department') {
      const dept = result as FranceDepartment;
      // Need to set region first, then department
      setSelectedLocation({
        level: 'department',
        region: { code: dept.region_code, name: dept.region_name } as FranceRegion,
        department: dept,
      });
      setBreadcrumbs([
        { level: 'region', code: dept.region_code, name: dept.region_name },
        { level: 'department', code: dept.code, name: dept.name },
      ]);
    } else if (type === 'commune') {
      const commune = result as FranceCommune;
      setSelectedLocation({
        level: 'commune',
        region: { code: commune.region_code, name: commune.region_name } as FranceRegion,
        department: { code: commune.department_code, name: commune.department_name } as FranceDepartment,
        commune,
      });
      setBreadcrumbs([
        { level: 'region', code: commune.region_code, name: commune.region_name },
        { level: 'department', code: commune.department_code, name: commune.department_name },
        { level: 'commune', code: commune.code, name: commune.name },
      ]);
    }
  }, [handleRegionSelect]);

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <MapPin className="w-6 h-6 text-primary-600" />
              Explore France
            </h1>
            <p className="mt-1 text-gray-600">
              Discover regions, departments, and towns. Generate detailed relocation reports.
            </p>
          </div>
          <LocationSearch onSelect={handleSearchSelect} />
        </div>
      </div>

      {/* Breadcrumb Navigation */}
      <nav className="mb-6" aria-label="Location breadcrumb">
        <ol className="flex items-center gap-2 text-sm">
          <li>
            <button
              onClick={() => handleBreadcrumbClick(-1)}
              className={`flex items-center gap-1 px-3 py-1.5 rounded-lg transition-colors ${
                selectedLocation.level === 'france'
                  ? 'bg-primary-100 text-primary-700 font-medium'
                  : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              <MapPin className="w-4 h-4" />
              France
            </button>
          </li>
          {breadcrumbs.map((crumb, index) => (
            <li key={crumb.code} className="flex items-center gap-2">
              <ChevronRight className="w-4 h-4 text-gray-400" />
              <button
                onClick={() => handleBreadcrumbClick(index)}
                className={`px-3 py-1.5 rounded-lg transition-colors ${
                  index === breadcrumbs.length - 1
                    ? 'bg-primary-100 text-primary-700 font-medium'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                {crumb.name}
              </button>
            </li>
          ))}
        </ol>
      </nav>

      {/* Main Content Area */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {selectedLocation.level === 'france' && (
          <FranceMap
            onRegionSelect={handleRegionSelect}
            onGenerateReport={(code, name) => handleGenerateReport('region', code, name)}
          />
        )}

        {selectedLocation.level === 'region' && selectedLocation.region && (
          <RegionView
            region={selectedLocation.region}
            onDepartmentSelect={handleDepartmentSelect}
            onGenerateReport={(code, name) => handleGenerateReport('region', code, name)}
            onBack={() => handleBreadcrumbClick(-1)}
          />
        )}

        {selectedLocation.level === 'department' && selectedLocation.department && (
          <DepartmentView
            department={selectedLocation.department}
            onCommuneSelect={handleCommuneSelect}
            onGenerateReport={(type, code, name) => handleGenerateReport(type, code, name)}
            onBack={() => handleBreadcrumbClick(0)}
          />
        )}

        {selectedLocation.level === 'commune' && selectedLocation.commune && (
          <CommuneView
            commune={selectedLocation.commune}
            onGenerateReport={(code, name) => handleGenerateReport('commune', code, name)}
          />
        )}
      </div>

      {/* Report Generation Modal */}
      {showReportModal && reportLocation && (
        <GenerateReportModal
          isOpen={showReportModal}
          onClose={() => setShowReportModal(false)}
          locationType={reportLocation.type}
          locationCode={reportLocation.code}
          locationName={reportLocation.name}
        />
      )}
    </div>
  );
}

/**
 * CommuneView - Display commune details
 */
function CommuneView({
  commune,
  onGenerateReport,
}: {
  commune: FranceCommune;
  onGenerateReport: (code: string, name: string) => void;
}) {
  return (
    <div className="p-6">
      <div className="flex items-start justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">{commune.name}</h2>
          <p className="text-gray-600">
            {commune.department_name}, {commune.region_name}
          </p>
        </div>
        <button
          onClick={() => onGenerateReport(commune.code, commune.name)}
          className="btn btn-primary"
        >
          <FileText className="w-4 h-4" />
          Generate Report
        </button>
      </div>

      <div className={`grid grid-cols-1 gap-4 mb-6 ${commune.population > 0 ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
        {commune.population > 0 && (
          <div className="bg-gray-50 rounded-lg p-4">
            <p className="text-sm text-gray-500">Population</p>
            <p className="text-2xl font-bold text-gray-900">
              {commune.population.toLocaleString()}
            </p>
          </div>
        )}
        <div className="bg-gray-50 rounded-lg p-4">
          <p className="text-sm text-gray-500">Type</p>
          <p className="text-2xl font-bold text-gray-900 capitalize">
            {commune.type}
          </p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <p className="text-sm text-gray-500">Department</p>
          <p className="text-2xl font-bold text-gray-900">
            {commune.department_name}
          </p>
        </div>
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 className="font-medium text-blue-900 mb-2">Generate a Detailed Report</h3>
        <p className="text-blue-700 text-sm mb-4">
          Get a comprehensive AI-researched guide about {commune.name}, including local history,
          cost of living, healthcare facilities, schools, transportation, and practical
          information for relocating to this area.
        </p>
        <button
          onClick={() => onGenerateReport(commune.code, commune.name)}
          className="btn btn-primary"
        >
          <FileText className="w-4 h-4" />
          Generate {commune.name} Report
        </button>
      </div>
    </div>
  );
}
