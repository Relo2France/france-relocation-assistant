/**
 * RegionView Component
 *
 * Displays region details with a map of departments.
 * Allows users to drill down into departments or generate region reports.
 */

import { useState, useEffect } from 'react';
import { ArrowLeft, FileText, MapPin, Users, Building2, Thermometer, Loader2 } from 'lucide-react';
import { getDepartmentsByRegion } from '@/config/research';
import { hasRegionMap } from '@/config/regionMaps';
import RegionMapView from './RegionMapView';
import type { FranceRegion, FranceDepartment } from '@/types';

interface RegionViewProps {
  region: FranceRegion;
  onDepartmentSelect: (department: FranceDepartment) => void;
  onGenerateReport: (code: string, name: string) => void;
  onBack: () => void;
}

// Climate type labels with icons
const CLIMATE_INFO: Record<string, { label: string; description: string }> = {
  oceanic: {
    label: 'Oceanic',
    description: 'Mild winters, cool summers, regular rainfall',
  },
  continental: {
    label: 'Continental',
    description: 'Cold winters, warm summers, moderate rainfall',
  },
  mediterranean: {
    label: 'Mediterranean',
    description: 'Mild winters, hot dry summers, sunny',
  },
  mountain: {
    label: 'Mountain',
    description: 'Cold winters with snow, cool summers',
  },
  'semi-oceanic': {
    label: 'Semi-Oceanic',
    description: 'Mix of oceanic and continental influences',
  },
};

// Department colors for visual differentiation
const DEPARTMENT_COLORS = [
  'bg-blue-100 hover:bg-blue-200 border-blue-200',
  'bg-emerald-100 hover:bg-emerald-200 border-emerald-200',
  'bg-purple-100 hover:bg-purple-200 border-purple-200',
  'bg-amber-100 hover:bg-amber-200 border-amber-200',
  'bg-rose-100 hover:bg-rose-200 border-rose-200',
  'bg-cyan-100 hover:bg-cyan-200 border-cyan-200',
  'bg-indigo-100 hover:bg-indigo-200 border-indigo-200',
  'bg-teal-100 hover:bg-teal-200 border-teal-200',
];

export default function RegionView({
  region,
  onDepartmentSelect,
  onGenerateReport,
  onBack,
}: RegionViewProps) {
  const [departments, setDepartments] = useState<FranceDepartment[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Get departments for this region
    setLoading(true);
    const regionDepartments = getDepartmentsByRegion(region.code);
    setDepartments(regionDepartments);
    setLoading(false);
  }, [region.code]);

  const climateInfo = CLIMATE_INFO[region.climate] || CLIMATE_INFO.oceanic;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex items-start justify-between mb-6">
        <div>
          <button
            onClick={onBack}
            className="flex items-center gap-1 text-gray-600 hover:text-gray-900 mb-2 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            <span className="text-sm">Back to map</span>
          </button>
          <h2 className="text-2xl font-bold text-gray-900">{region.name}</h2>
          <p className="text-gray-600 mt-1">{region.description}</p>
        </div>
        <button
          onClick={() => onGenerateReport(region.code, region.name)}
          className="btn btn-primary flex items-center gap-2"
        >
          <FileText className="w-4 h-4" />
          Generate Report
        </button>
      </div>

      {/* Region Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <MapPin className="w-4 h-4" />
            Capital
          </div>
          <p className="text-lg font-semibold text-gray-900">{region.capital}</p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <Users className="w-4 h-4" />
            Population
          </div>
          <p className="text-lg font-semibold text-gray-900">
            {region.population.toLocaleString()}
          </p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <Building2 className="w-4 h-4" />
            Departments
          </div>
          <p className="text-lg font-semibold text-gray-900">
            {region.departments.length}
          </p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <Thermometer className="w-4 h-4" />
            Climate
          </div>
          <p className="text-lg font-semibold text-gray-900">{climateInfo.label}</p>
          <p className="text-xs text-gray-500 mt-1">{climateInfo.description}</p>
        </div>
      </div>

      {/* Regional Map (if available) */}
      {!loading && hasRegionMap(region.code) && (
        <RegionMapView
          regionCode={region.code}
          regionName={region.name}
          departments={departments}
          onDepartmentSelect={onDepartmentSelect}
        />
      )}

      {/* Departments Section */}
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {hasRegionMap(region.code) ? 'All Departments' : `Departments in ${region.name}`}
        </h3>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-primary-600" />
          </div>
        ) : departments.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {departments.map((dept, index) => (
              <button
                key={dept.code}
                onClick={() => onDepartmentSelect(dept)}
                className={`text-left p-4 rounded-lg border transition-all ${
                  DEPARTMENT_COLORS[index % DEPARTMENT_COLORS.length]
                }`}
              >
                <div className="flex items-start justify-between">
                  <div>
                    <span className="text-xs font-mono text-gray-500 bg-white/50 px-1.5 py-0.5 rounded">
                      {dept.code}
                    </span>
                    <h4 className="font-semibold text-gray-900 mt-1">{dept.name}</h4>
                    <p className="text-sm text-gray-600 mt-1">
                      Prefecture: {dept.prefecture}
                    </p>
                  </div>
                </div>
                <div className="mt-3 flex items-center justify-between text-sm">
                  <span className="text-gray-500">
                    Pop: {dept.population.toLocaleString()}
                  </span>
                  <span className="text-primary-600 font-medium">
                    Explore →
                  </span>
                </div>
              </button>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-500">
            <Building2 className="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p>Department data coming soon for {region.name}</p>
          </div>
        )}
      </div>

      {/* Info Box */}
      <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 className="font-medium text-blue-900 mb-2">
          About Region Reports
        </h4>
        <p className="text-blue-700 text-sm">
          Generate a comprehensive AI-researched report about {region.name} including
          cost of living, healthcare, education, cultural highlights, and practical
          relocation information. Reports are cached for faster access and updated
          when regenerated.
        </p>
      </div>
    </div>
  );
}
