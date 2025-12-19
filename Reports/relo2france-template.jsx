import React, { useState } from 'react';
import { Mountain, Sun, Users, Grape, MapPin, TrendingUp, Heart, BookOpen, ChevronDown, ChevronUp, Utensils, TreePine, Thermometer, Building2, Languages, Music, Anchor, Wine, Waves } from 'lucide-react';

// Brand colors from relo2france logo
const brandColors = {
  blue: "#4A7BA7",
  gold: "#E5A54B",
  darkText: "#2D3748",
  lightGray: "#718096",
  lightBlue: "#EBF4FA"
};

// IMPORTANT: Replace with base64 data URI after conversion
// Logo at /mnt/user-data/uploads/relo2france_updated_logo.png is WebP format
// Convert first: convert [input] /home/claude/logo.png && base64 -w 0 /home/claude/logo.png
const logo = "LOGO_PLACEHOLDER";

export default function LocationOverview() {
  const [expandedSections, setExpandedSections] = useState({});

  const toggleSection = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const Section = ({ icon: Icon, title, children, color = "blue", defaultExpanded = true }) => {
    const sectionKey = title.toLowerCase().replace(/\s+/g, '-');
    const isExpanded = expandedSections[sectionKey] ?? defaultExpanded;
    
    return (
      <div className="mb-6 rounded-lg border border-slate-200 overflow-hidden">
        <button
          onClick={() => toggleSection(sectionKey)}
          className="w-full flex items-center justify-between p-4 bg-white hover:bg-slate-50 transition-colors"
        >
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-lg" style={{ backgroundColor: brandColors.lightBlue }}>
              <Icon className="w-5 h-5" style={{ color: brandColors.blue }} />
            </div>
            <h3 className="text-lg font-semibold" style={{ color: brandColors.darkText }}>{title}</h3>
          </div>
          {isExpanded ? <ChevronUp className="w-5 h-5" style={{ color: brandColors.lightGray }} /> : <ChevronDown className="w-5 h-5" style={{ color: brandColors.lightGray }} />}
        </button>
        {isExpanded && (
          <div className="p-4 pt-0 bg-white">
            <div className="pt-2 border-t border-slate-100">{children}</div>
          </div>
        )}
      </div>
    );
  };

  const StatCard = ({ label, value, sublabel }) => (
    <div className="p-3 rounded-lg text-center" style={{ backgroundColor: brandColors.lightBlue }}>
      <div className="text-2xl font-bold" style={{ color: brandColors.blue }}>{value}</div>
      <div className="text-sm font-medium" style={{ color: brandColors.darkText }}>{label}</div>
      {sublabel && <div className="text-xs" style={{ color: brandColors.lightGray }}>{sublabel}</div>}
    </div>
  );

  const DataRow = ({ label, value }) => (
    <div className="flex justify-between py-1 border-b border-slate-100 last:border-0">
      <span style={{ color: brandColors.lightGray }}>{label}</span>
      <span className="font-medium" style={{ color: brandColors.darkText }}>{value}</span>
    </div>
  );

  return (
    <div className="min-h-screen p-6" style={{ backgroundColor: '#F7FAFC' }}>
      <div className="max-w-4xl mx-auto">
        {/* Header with Logo */}
        <div className="text-center mb-8">
          <img 
            src={logo} 
            alt="relo2france" 
            className="mx-auto mb-6"
            style={{ maxWidth: '250px', height: 'auto' }}
          />
          <h1 className="text-4xl font-bold mb-2" style={{ color: brandColors.blue }}>[LOCATION NAME]</h1>
          <h2 className="text-xl mb-2" style={{ color: brandColors.gold }}>[Department ## • Region Name]</h2>
          <p className="text-lg" style={{ color: brandColors.darkText }}>[Primary Tagline]</p>
          <p style={{ color: brandColors.lightGray }}>[Secondary Description]</p>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <StatCard label="Area" value="[X,XXX]" sublabel="km²" />
          <StatCard label="Population" value="[X.XXM]" sublabel="2024 estimate" />
          <StatCard label="Préfecture" value="[City]" sublabel="[descriptor]" />
          <StatCard label="[Key Metric]" value="[Value]" sublabel="[context]" />
        </div>

        {/* ==================== GEOGRAPHY ==================== */}
        <Section icon={Mountain} title="Geography & Landscape" color="blue">
          <p className="mb-3">[Opening paragraph describing physical geography, location, and defining characteristics. Include <span className="font-semibold" style={{ color: brandColors.blue }}>highlighted key facts</span> inline.]</p>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <StatCard label="[Metric]" value="[Value]" sublabel="[context]" />
            <StatCard label="[Metric]" value="[Value]" sublabel="[context]" />
            <StatCard label="[Metric]" value="[Value]" sublabel="[context]" />
            <StatCard label="[Metric]" value="[Value]" sublabel="[context]" />
          </div>
          
          <div className="rounded-lg p-4 mt-4" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Major Geographic Features</h4>
            <div className="grid grid-cols-2 gap-2 text-sm">
              <div><span className="font-medium">[Feature]:</span> [Description]</div>
              <div><span className="font-medium">[Feature]:</span> [Description]</div>
              <div><span className="font-medium">[Feature]:</span> [Description]</div>
              <div><span className="font-medium">[Feature]:</span> [Description]</div>
            </div>
          </div>
          
          <p className="mt-3 mb-3">[Additional geographic details, terrain types, landscape zones.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Supplementary information, interesting geographic facts, geological context.]</p>
        </Section>

        {/* ==================== CLIMATE ==================== */}
        <Section icon={Thermometer} title="Climate" color="orange">
          <p className="mb-3">[Opening describing climate type and general characteristics.]</p>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <StatCard label="Avg. Annual" value="[X°C]" sublabel="[descriptor]" />
            <StatCard label="Summer High" value="[X-X°C]" sublabel="[months]" />
            <StatCard label="Winter Low" value="[X-X°C]" sublabel="[months]" />
            <StatCard label="Rainfall" value="[Xmm]" sublabel="annual average" />
          </div>
          
          <div className="rounded-lg p-4 mb-3 border" style={{ backgroundColor: '#FEF3E2', borderColor: '#f5d9a8' }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.gold }}>Seasonal Characteristics</h4>
            <div className="text-sm space-y-1">
              <p><span className="font-medium">Spring (Mar-May):</span> [Description]</p>
              <p><span className="font-medium">Summer (Jun-Aug):</span> [Description]</p>
              <p><span className="font-medium">Autumn (Sep-Nov):</span> [Description]</p>
              <p><span className="font-medium">Winter (Dec-Feb):</span> [Description]</p>
            </div>
          </div>
          
          <p className="mb-3">[Regional climate variations, microclimates, notable weather patterns.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Climate change impacts, considerations for residents.]</p>
        </Section>

        {/* ==================== DEMOGRAPHICS ==================== */}
        <Section icon={Users} title="Population & Demographics" color="purple">
          <p className="mb-3">[Opening with population figures and growth context.]</p>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <StatCard label="Population" value="[X.XXM]" sublabel="2024 estimate" />
            <StatCard label="Density" value="[X/km²]" sublabel="[vs national avg]" />
            <StatCard label="[Urban Area]" value="[X.XM]" sublabel="metropolitan area" />
            <StatCard label="Growth" value="[±X.X%]" sublabel="annual" />
          </div>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Major Population Centers</h4>
            <DataRow label="[City 1]" value="~[XXX,000]" />
            <DataRow label="[City 2]" value="~[XX,000]" />
            <DataRow label="[City 3]" value="~[XX,000]" />
            <DataRow label="[City 4]" value="~[XX,000]" />
          </div>
          
          <p className="mb-3">[Population distribution, urban vs rural, notable demographic features.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Age demographics, migration patterns, expat communities.]</p>
        </Section>

        {/* ==================== ECONOMY ==================== */}
        <Section icon={TrendingUp} title="Economy" color="green">
          <p className="mb-3">[Opening describing economic character and major industries.]</p>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <StatCard label="GDP/capita" value="€[XX,000]" sublabel="[vs national]" />
            <StatCard label="[Key Export]" value="€[X.X]B" sublabel="annual value" />
            <StatCard label="Employment" value="[XXX,000]" sublabel="total jobs" />
            <StatCard label="Tourism" value="[X]M+" sublabel="visitors annually" />
          </div>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Key Economic Sectors</h4>
            <div className="text-sm space-y-2">
              <p><span className="font-medium">[Sector 1]:</span> [Description with figures]</p>
              <p><span className="font-medium">[Sector 2]:</span> [Description with figures]</p>
              <p><span className="font-medium">[Sector 3]:</span> [Description with figures]</p>
              <p><span className="font-medium">[Sector 4]:</span> [Description with figures]</p>
            </div>
          </div>
          
          <p className="mb-3">[Recent economic developments, major employers, infrastructure projects.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Employment distribution, unemployment rates, economic outlook.]</p>
        </Section>

        {/* ==================== LANGUAGE ==================== */}
        <Section icon={Languages} title="Language & Identity" color="indigo">
          <p className="mb-3">[Opening about linguistic landscape.]</p>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Linguistic Heritage</h4>
            <div className="text-sm space-y-2">
              <p><span className="font-medium">[Language/Dialect]:</span> [Description and current status]</p>
              <p><span className="font-medium">[Regional French]:</span> [Accent and vocabulary notes]</p>
              <p><span className="font-medium">[International]:</span> [English usage, expat communities]</p>
            </div>
          </div>
          
          <p className="mb-3">[Regional identity, cultural distinctiveness, local terminology.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Historical linguistic context, place name etymology.]</p>
        </Section>

        {/* ==================== GASTRONOMY ==================== */}
        <Section icon={Utensils} title="Gastronomy" color="amber">
          <p className="mb-3">[Opening describing culinary character and influences.]</p>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Signature Dishes & Products</h4>
            <div className="grid grid-cols-2 gap-2 text-sm">
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
              <div><span className="font-medium">[Dish/Product]:</span> [Description]</div>
            </div>
          </div>
          
          <p className="mb-3">[Local food production, markets, AOC products, seasonal specialties.]</p>
          
          <div className="rounded-lg p-3 mb-3 border bg-amber-50 border-amber-100">
            <h4 className="font-semibold mb-2 text-amber-800">[Signature Item]</h4>
            <p className="text-sm text-amber-700">[Detailed description of an iconic local food or drink.]</p>
          </div>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Restaurant scene, Michelin recognition, food culture notes.]</p>
        </Section>

        {/* ==================== CULTURE ==================== */}
        <Section icon={Music} title="Culture & Lifestyle" color="pink">
          <p className="mb-3">[Opening describing cultural character and lifestyle.]</p>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Cultural Highlights</h4>
            <div className="text-sm space-y-2">
              <p><span className="font-medium">UNESCO World Heritage:</span> [Sites if applicable]</p>
              <p><span className="font-medium">[Museum/Institution]:</span> [Description]</p>
              <p><span className="font-medium">[Cultural Feature]:</span> [Description]</p>
              <p><span className="font-medium">[Festival/Event]:</span> [Description]</p>
            </div>
          </div>
          
          <p className="mb-3">[Architectural heritage, arts scene, local traditions.]</p>
          
          <p className="mb-3">[Lifestyle characteristics, pace of life, recreational culture.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Major events, sports culture, community life.]</p>
        </Section>

        {/* ==================== ADMINISTRATIVE ==================== */}
        <Section icon={MapPin} title="Arrondissements & Geography" color="teal">
          <p className="mb-3">[Opening about administrative structure.]</p>
          
          <div className="rounded-lg p-4 mb-3" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>[Number] Arrondissements</h4>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
              <div><span className="font-medium">[Name]:</span> [Description]</div>
              <div><span className="font-medium">[Name]:</span> [Description]</div>
              <div><span className="font-medium">[Name]:</span> [Description]</div>
              <div><span className="font-medium">[Name]:</span> [Description]</div>
            </div>
          </div>
          
          <div className="rounded-lg p-4 mb-3 border bg-teal-50 border-teal-100">
            <h4 className="font-semibold mb-2 text-teal-800">Notable Communes</h4>
            <div className="text-sm text-teal-700 space-y-1">
              <p><span className="font-medium">[Commune]:</span> [Description]</p>
              <p><span className="font-medium">[Commune]:</span> [Description]</p>
              <p><span className="font-medium">[Commune]:</span> [Description]</p>
              <p><span className="font-medium">[Commune]:</span> [Description]</p>
            </div>
          </div>
          
          <p className="mb-3">[Local governance, conseil départemental/régional, intercommunal structures.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Administrative details, département responsibilities, commune functions.]</p>
        </Section>

        {/* ==================== QUALITY OF LIFE ==================== */}
        <Section icon={Heart} title="Quality of Life" color="rose">
          <p className="mb-3">[Opening about overall quality of life and appeal.]</p>
          
          <div className="rounded-lg p-4 mb-4" style={{ backgroundColor: brandColors.lightBlue }}>
            <h4 className="font-semibold mb-3" style={{ color: brandColors.blue }}>Key Quality Indicators</h4>
            <DataRow label="[Transport connection]" value="[Time/distance]" />
            <DataRow label="[Airport]" value="[Destinations/capacity]" />
            <DataRow label="[Education metric]" value="[Figure]" />
            <DataRow label="[Healthcare metric]" value="[Description]" />
            <DataRow label="[Lifestyle metric]" value="[Figure]" />
          </div>
          
          <p className="mb-3"><span className="font-medium">Healthcare:</span> [Hospital system, specialist availability, rural access.]</p>
          
          <p className="mb-3"><span className="font-medium">Education:</span> [Universities, international schools, vocational training.]</p>
          
          <p className="mb-3"><span className="font-medium">Transport:</span> [Rail, road, public transit, cycling infrastructure.]</p>
          
          <div className="rounded-lg p-3 border" style={{ backgroundColor: '#FEF3E2', borderColor: '#f5d9a8' }}>
            <p className="text-sm" style={{ color: brandColors.darkText }}><span className="font-medium" style={{ color: brandColors.gold }}>Considerations:</span> [Honest assessment of challenges - property prices, climate issues, bureaucracy, rural limitations, etc.]</p>
          </div>
        </Section>

        {/* ==================== ENVIRONMENT ==================== */}
        <Section icon={TreePine} title="Environment & Natural Heritage" color="green">
          <p className="mb-3">[Opening about environmental diversity and natural assets.]</p>
          
          <p className="mb-3"><span className="font-medium">[Major Natural Feature]:</span> [Detailed description of signature natural area.]</p>
          
          <div className="rounded-lg p-4 mb-3 border bg-emerald-50 border-emerald-100">
            <h4 className="font-semibold mb-2 text-emerald-800">Natural Highlights</h4>
            <div className="text-sm text-emerald-700 space-y-1">
              <p><span className="font-medium">[Feature]:</span> [Description]</p>
              <p><span className="font-medium">[Feature]:</span> [Description]</p>
              <p><span className="font-medium">[Feature]:</span> [Description]</p>
              <p><span className="font-medium">[Feature]:</span> [Description]</p>
            </div>
          </div>
          
          <p className="mb-3"><span className="font-medium">Conservation Challenges:</span> [Environmental issues, climate impacts, protection efforts.]</p>
          
          <p className="text-sm" style={{ color: brandColors.lightGray }}>[Outdoor recreation, hiking trails, nature activities, seasonal considerations.]</p>
        </Section>

        {/* ==================== FOOTER ==================== */}
        <div className="mt-8 pt-6 border-t border-slate-200 text-center">
          <p className="text-sm mb-2" style={{ color: brandColors.lightGray }}>Data from INSEE, [Regional sources], and official French government sources (2024-2025)</p>
          <p className="text-sm" style={{ color: brandColors.blue }}>insee.fr • [regional-site.fr] • [other-source.fr]</p>
          <div className="mt-4 pt-4 border-t border-slate-100">
            <p className="text-sm">
              <span style={{ color: brandColors.blue }}>relo</span>
              <span className="font-bold" style={{ color: brandColors.gold }}>2</span>
              <span style={{ color: brandColors.blue }}>france</span>
              <span style={{ color: brandColors.lightGray }}>.com</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
