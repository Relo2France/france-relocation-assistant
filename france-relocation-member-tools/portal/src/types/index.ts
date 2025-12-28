// Project types
export interface Project {
  id: number;
  user_id: number;
  title: string;
  description: string;
  visa_type: VisaType;
  visa_type_label: string;
  current_stage: string;
  target_move_date: string | null;
  days_until_move: number | null;
  status: ProjectStatus;
  status_label: string;
  settings: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export type VisaType = 'visitor' | 'talent' | 'student' | 'family' | 'work' | 'other';
export type ProjectStatus = 'active' | 'completed' | 'paused' | 'archived';

// Task types
export interface Task {
  id: number;
  project_id: number;
  user_id: number;
  title: string;
  description: string;
  stage: string;
  status: TaskStatus;
  status_label: string;
  priority: TaskPriority;
  priority_label: string;
  task_type: TaskType;
  task_type_label: string;
  due_date: string | null;
  days_until_due: number | null;
  is_overdue: boolean;
  assignee_id: number | null;
  assignee_name: string | null;
  portal_visible: boolean;
  sort_order: number;
  parent_task_id: number | null;
  metadata: Record<string, unknown>;
  completed_at: string | null;
  created_at: string;
  updated_at: string;
  subtasks?: Task[];
}

export type TaskStatus = 'todo' | 'in_progress' | 'waiting' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';
export type TaskType = 'client' | 'team' | 'system';

// Stage types
export interface Stage {
  id: number;
  visa_type: string;
  slug: string;
  title: string;
  description: string;
  sort_order: number;
  color: string;
  icon: string;
  created_at: string;
}

export interface StageProgress extends Stage {
  total: number;
  completed: number;
  percentage: number;
  is_current: boolean;
  is_completed: boolean;
  status: 'completed' | 'current' | 'upcoming';
}

// Activity types
export interface Activity {
  id: number;
  project_id: number;
  user_id: number;
  user_name: string;
  user_avatar: string;
  action: string;
  action_label: string;
  action_icon: string;
  entity_type: string;
  entity_id: number;
  description: string;
  metadata: Record<string, unknown>;
  created_at: string;
  relative_time: string;
}

export interface ActivityGroup {
  date: string;
  label: string;
  activities: Activity[];
}

// Dashboard types
export interface WelcomeBanner {
  title: string;
  message: string;
  bg_color: string;
  border_color: string;
}

export interface DashboardData {
  project: Project;
  stages: StageProgress[];
  task_stats: TaskStats;
  profile_visa_type: string | null;
  profile_visa_label: string | null;
  welcome_banner: WelcomeBanner | null;
  upcoming_tasks: Task[];
  overdue_tasks: Task[];
  recent_activity: Activity[];
}

export interface TaskStats {
  total: number;
  completed: number;
  in_progress: number;
  todo: number;
  overdue: number;
  percentage: number;
}

// User types
export interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  first_name: string;
  last_name: string;
  avatar_url: string;
  roles: string[];
  is_admin: boolean;
  active_memberships?: number[];
  is_member?: boolean;
}

export interface UserSettings {
  email_notifications: boolean;
  task_reminders: boolean;
  weekly_digest: boolean;
  language: string;
  timezone: string;
  date_format: string;
  menu_order?: MenuSectionOrder;
}

// Menu section ordering for drag-and-drop
export interface MenuSectionOrder {
  project: string[];
  resources: string[];
  account: string[];
}

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  display_name?: string;
}

// File types
export interface PortalFile {
  id: number;
  project_id: number;
  user_id: number;
  filename: string;
  original_name: string;
  file_type: FileType;
  file_type_label: string;
  mime_type: string;
  file_size: number;
  file_size_formatted: string;
  category: FileCategory;
  category_label: string;
  description: string | null;
  is_generated: boolean;
  entity_type: string | null;
  entity_id: number | null;
  thumbnail_url: string | null;
  preview_url: string | null;
  download_url: string;
  uploaded_by: number;
  uploaded_by_name: string;
  created_at: string;
  updated_at: string;
}

export type FileType = 'document' | 'image' | 'pdf' | 'spreadsheet' | 'archive' | 'other';
export type FileCategory = 'identity' | 'financial' | 'housing' | 'employment' | 'visa' | 'medical' | 'education' | 'other';

// Note types
export interface Note {
  id: number;
  project_id: number;
  user_id: number;
  user_name: string;
  user_avatar: string;
  task_id: number | null;
  content: string;
  is_pinned: boolean;
  visibility: NoteVisibility;
  created_at: string;
  updated_at: string;
  relative_time: string;
}

export type NoteVisibility = 'private' | 'team' | 'shared';

// Filter types for API queries
export interface TaskFilters {
  stage?: string;
  status?: string;
  task_type?: string;
}

export interface FileFilters {
  category?: FileCategory;
  file_type?: string;
}

export interface NoteFilters {
  task_id?: number;
  pinned?: boolean;
}

// API Response types
export interface ApiError {
  code: string;
  message: string;
  status: number;
}

// Navigation types
export interface NavItem {
  id: string;
  label: string;
  icon: string;
  path: string;
  badge?: number;
}

export interface NavSection {
  id: string;
  label: string;
  items: NavItem[];
}

// Portal Settings types (from PHP admin settings)
export interface PortalSettings {
  colors: {
    primary: string;
    secondary: string;
    accent: string;
    sidebarBg: string;
    sidebarText: string;
    headerBg: string;
  };
  layout: {
    showWpHeader: boolean;
    showWpFooter: boolean;
    showPromoBanner: boolean;
    sidebarPosition: 'left' | 'right';
    sidebarCollapsed: boolean;
  };
  branding: {
    title: string;
    logoUrl: string;
  };
  features: {
    notifications: boolean;
    fileUpload: boolean;
  };
  menu: MenuItem[];
  customCss: string;
}

export interface MenuItem {
  id: string;
  label: string;
  icon: string;
  path: string;
}

export interface PortalUser {
  id: number;
  email: string;
  displayName: string;
  firstName: string;
  lastName: string;
  avatar: string;
}

export interface PortalApi {
  root: string;
  nonce: string;
}

// Full Profile types (30+ fields for visa applications)
export interface MemberProfile {
  // Personal Information
  legal_first_name: string;
  legal_middle_name: string;
  legal_last_name: string;
  date_of_birth: string;
  nationality: string;
  passport_number: string;
  passport_expiry: string;

  // Applicant Information
  applicants: ApplicantType;
  spouse_legal_first_name: string;
  spouse_legal_last_name: string;
  spouse_date_of_birth: string;
  spouse_name: string;
  spouse_work_status: WorkStatus;
  num_children: number;
  children_ages: string;
  has_pets: PetType;
  pet_details: string;

  // Visa & Employment
  visa_type: ProfileVisaType;
  employment_status: WorkStatus;
  work_in_france: WorkInFranceType;
  industry: string;
  employer_name: string;

  // Location Information
  current_country: string;
  current_state: string;
  current_city: string;
  birth_state: string;
  spouse_birth_state: string;
  marriage_state: string;
  marriage_country: string;
  target_location: string;
  application_location: ApplicationLocation;

  // Timeline
  timeline: TimelineType;
  target_move_date: string;
  move_date_certainty: MoveDateCertainty;

  // Financial Information
  financial_resources: FinancialRange;
  income_sources: string;

  // Metadata
  profile_completion: number;
  created_at: string;
  updated_at: string;
}

export type ApplicantType = 'alone' | 'spouse' | 'spouse_kids' | 'kids_only';
export type WorkStatus = 'employed' | 'self_employed' | 'retired' | 'not_working';
export type PetType = 'no' | 'dogs' | 'cats' | 'both' | 'other';
export type ProfileVisaType = 'undecided' | 'visitor' | 'talent_passport' | 'employee' | 'entrepreneur' | 'student' | 'family' | 'spouse_french' | 'retiree';
export type WorkInFranceType = 'no' | 'yes_local' | 'yes_remote' | 'yes_self' | 'undecided';
export type ApplicationLocation = 'us' | 'france';
export type FinancialRange = 'under_50k' | '50k_100k' | '100k_200k' | '200k_500k' | 'over_500k';
export type TimelineType = 'asap' | '1_3_months' | '3_6_months' | '6_12_months' | '12_plus_months' | 'flexible';
export type MoveDateCertainty = 'fixed' | 'anticipated' | 'flexible';

// Checklist types
export interface Checklist {
  id: string;
  title: string;
  type: ChecklistType;
  visa_type: string;
  items: ChecklistItem[];
  completion_percentage: number;
}

export interface ChecklistItem {
  id: string;
  checklist_type: ChecklistType;
  title: string;
  description?: string;
  lead_time: string;
  status: ChecklistItemStatus;
  handled_own: boolean;
  notes: string;
  due_date?: string;
  completed_at?: string;
  sort_order: number;
}

export type ChecklistType = 'visa-application' | 'relocation' | 'task-checklist';
export type ChecklistItemStatus = 'pending' | 'in_progress' | 'complete';

// Task Checklist (mini checklists within tasks)
export interface TaskChecklist {
  task_id: number;
  items: TaskChecklistItem[];
}

export interface TaskChecklistItem {
  id: string;
  title: string;
  completed: boolean;
  sort_order: number;
}

// Document Generation types
export interface GeneratedDocument {
  id: number;
  project_id: number;
  user_id: number;
  document_type: GeneratedDocumentType;
  document_type_label: string;
  language: 'en' | 'fr';
  filename: string;
  file_url: string;
  answers: Record<string, unknown>;
  created_at: string;
}

export type GeneratedDocumentType = 'cover-letter' | 'financial-statement' | 'no-work-attestation' | 'accommodation-letter';

export interface DocumentGenerationRequest {
  document_type: GeneratedDocumentType;
  language: 'en' | 'fr';
  answers: Record<string, unknown>;
}

export interface DocumentGenerationResponse {
  success: boolean;
  document?: GeneratedDocument;
  preview?: DocumentPreview;
  error?: string;
}

export interface DocumentPreview {
  type: GeneratedDocumentType;
  language: string;
  content: DocumentContent;
}

export interface DocumentContent {
  header?: {
    date: string;
    recipient: string[];
  };
  subject?: string;
  salutation?: string;
  paragraphs: string[];
  closing?: string;
  signature?: {
    line: string;
    name: string;
    date_line: string;
  };
}

// Glossary types
export interface GlossaryCategory {
  id: string;
  title: string;
  terms: GlossaryTerm[];
}

export interface GlossaryTerm {
  id: string;
  title: string;
  french?: string;
  short: string;
  full?: string;
  category: string;
}

// AI Verification types
export interface VerificationRequest {
  file_id: number;
  verification_type: VerificationType;
}

export interface VerificationResult {
  success: boolean;
  status: VerificationStatus;
  message: string;
  details?: VerificationDetails;
  error?: string;
}

export interface VerificationDetails {
  coverage_type?: string;
  coverage_territory?: string;
  coverage_duration?: string;
  start_date?: string;
  end_date?: string;
  provider?: string;
  issues?: string[];
  recommendations?: string[];
}

export type VerificationType = 'health-insurance' | 'financial' | 'accommodation';
export type VerificationStatus = 'passed' | 'failed' | 'needs_review' | 'error';

// Personalized Guide types
export interface PersonalizedGuide {
  id: string;
  title: string;
  type: GuideType;
  icon: string;
  description: string;
  is_personalized: boolean;
  sections: GuideSection[];
  metadata?: GuideMetadata;
}

export interface GuideSection {
  id: string;
  title: string;
  content: string;
  tips?: string[];
  warnings?: string[];
}

export interface GuideMetadata {
  visa_type?: string;
  states?: StateApostilleInfo[];
  estimated_time?: string;
  last_updated?: string;
}

export interface StateApostilleInfo {
  state: string;
  state_name: string;
  document: string;
  agency: string;
  method: string;
  cost: string;
  processing_time: string;
  website?: string;
  notes?: string;
}

export type GuideType = 'visa-application' | 'apostille' | 'pet-relocation' | 'french-mortgages' | 'bank-ratings' | 'healthcare' | 'housing' | 'general';

// Knowledge Base Chat types
export interface ChatMessage {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  timestamp: string;
  sources?: ChatSource[];
}

export interface ChatSource {
  title: string;
  category: string;
  relevance?: number;
  url?: string;
  type?: 'official' | 'community';
}

export interface ChatRequest {
  message: string;
  context?: string;
  include_practice?: boolean;
}

export interface ChatResponse {
  success: boolean;
  message?: string;
  sources?: ChatSource[];
  is_premium_topic?: boolean;
  error?: string;
}

export interface KnowledgeCategory {
  id: string;
  title: string;
  icon: string;
  description: string;
  topics: KnowledgeTopic[];
}

export interface KnowledgeTopic {
  id: string;
  title: string;
  keywords: string[];
  is_premium: boolean;
}

// MemberPress types
export interface Subscription {
  id: number;
  membership_id: number;
  membership_title: string;
  status: SubscriptionStatus;
  status_label: string;
  price: string;
  billing_period: string;
  created_at: string;
  expires_at: string | null;
  next_billing_date: string | null;
  can_cancel: boolean;
  can_suspend: boolean;
  can_resume: boolean;
  can_upgrade: boolean;
}

export type SubscriptionStatus = 'active' | 'cancelled' | 'suspended' | 'expired' | 'pending';

export interface Payment {
  id: number;
  subscription_id: number;
  amount: string;
  status: PaymentStatus;
  status_label: string;
  payment_method: string;
  transaction_id: string;
  created_at: string;
}

export type PaymentStatus = 'complete' | 'pending' | 'failed' | 'refunded';

export interface MembershipInfo {
  is_member: boolean;
  membership_level: string | null;
  membership_id: number | null;
  expires_at: string | null;
  subscriptions: Subscription[];
  payments: Payment[];
}

export interface UpgradeOption {
  id: number;
  title: string;
  price: string;
  features: string[];
}

// France Research Tool types
export interface FranceRegion {
  code: string;              // "84" (Auvergne-Rhône-Alpes)
  name: string;              // "Auvergne-Rhône-Alpes"
  capital: string;           // "Lyon"
  population: number;
  area_km2: number;
  departments: string[];     // ["01", "03", "07", ...]
  climate: ClimateType;
  description: string;
}

export interface FranceDepartment {
  code: string;              // "33"
  name: string;              // "Gironde"
  region_code: string;       // "75"
  region_name: string;       // "Nouvelle-Aquitaine"
  prefecture: string;        // "Bordeaux"
  population: number;
  area_km2: number;
  major_cities: string[];    // Top cities
}

export interface FranceCommune {
  code: string;              // "33063" (INSEE code)
  name: string;              // "Bordeaux"
  postal_codes: string[];    // ["33000", "33100", ...]
  department_code: string;
  department_name: string;
  region_code: string;
  region_name: string;
  population: number;
  type: CommuneType;
  coordinates?: {
    lat: number;
    lng: number;
  };
}

export type ClimateType = 'oceanic' | 'continental' | 'mediterranean' | 'mountain' | 'semi-oceanic';
export type CommuneType = 'city' | 'town' | 'village';
export type ResearchLevel = 'region' | 'department' | 'commune';

export interface LocationBreadcrumb {
  level: ResearchLevel;
  code: string;
  name: string;
}

// Research Report types
export interface ResearchReport {
  id: number;
  location_type: ResearchLevel;
  location_code: string;
  location_name: string;
  region_name?: string;
  department_name?: string;
  content: ReportContent;
  version: number;
  generated_at: string;
  updated_at: string;
  download_url: string;
}

export interface ReportContent {
  title: string;
  subtitle: string;
  generated_date: string;
  sections: ReportSection[];
}

export interface ReportSection {
  id: string;
  title: string;
  content: string;
  subsections?: ReportSubsection[];
}

export interface ReportSubsection {
  title: string;
  content: string;
  data?: Record<string, string | number>;
}

export interface GenerateReportRequest {
  location_type: ResearchLevel;
  location_code: string;
  save_to_documents?: boolean;
}

export interface GenerateReportResponse {
  success: boolean;
  report?: ResearchReport;
  cached?: boolean;
  saved_to_documents?: boolean;
  document_id?: number;
  error?: string;
}

export interface SavedResearchDocument {
  id: number;
  report_id: number;
  location_type: ResearchLevel;
  location_code: string;
  location_name: string;
  report_updated_at: string;
  saved_at: string;
}

// Support Ticket types
export interface SupportTicket {
  id: number;
  user_id: number;
  subject: string;
  status: TicketStatus;
  priority: TicketPriority;
  has_unread_user: boolean;
  reply_count: number;
  initial_message?: string;
  created_at: string;
  updated_at: string;
  closed_at?: string | null;
  relative_time: string;
  last_reply_at?: string;
}

export type TicketStatus = 'open' | 'closed';
export type TicketPriority = 'normal' | 'high' | 'urgent';

export interface TicketReply {
  id: number;
  message_id: number;
  user_id: number;
  content: string;
  is_admin: boolean;
  author_name: string;
  created_at: string;
  relative_time: string;
}

export interface SupportTicketsResponse {
  tickets: SupportTicket[];
  unread_count: number;
}

export interface SupportTicketDetailResponse {
  ticket: SupportTicket;
  replies: TicketReply[];
}

export interface CreateTicketRequest {
  subject: string;
  content: string;
}

export interface TicketReplyRequest {
  content: string;
}

// ============================================
// Schengen Tracker Types
// ============================================

export const SCHENGEN_COUNTRIES = [
  'Austria', 'Belgium', 'Bulgaria', 'Croatia', 'Czech Republic', 'Denmark',
  'Estonia', 'Finland', 'France', 'Germany', 'Greece', 'Hungary', 'Iceland',
  'Italy', 'Latvia', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Malta',
  'Netherlands', 'Norway', 'Poland', 'Portugal', 'Romania', 'Slovakia',
  'Slovenia', 'Spain', 'Sweden', 'Switzerland'
] as const;

export type SchengenCountry = typeof SCHENGEN_COUNTRIES[number];

export interface SchengenTrip {
  id: string;                              // UUID
  startDate: string;                       // ISO date (YYYY-MM-DD)
  endDate: string;                         // ISO date (YYYY-MM-DD)
  country: SchengenCountry | string;       // Country/state name (string for non-Schengen)
  jurisdictionCode?: string;               // Jurisdiction code (default: 'schengen')
  category: 'personal' | 'business';
  notes?: string;
  createdAt: string;                       // ISO timestamp
  updatedAt: string;                       // ISO timestamp
}

export interface SchengenSummary {
  daysUsed: number;                        // Days in current 180-day window
  daysRemaining: number;                   // 90 - daysUsed
  windowStart: string;                     // Date 180 days ago
  windowEnd: string;                       // Today
  status: SchengenStatus;                  // Compliance status
  nextExpiration: string | null;           // When oldest days drop off
  statusThresholds: {
    yellow: number;                        // Default: 60
    red: number;                           // Default: 80
  };
}

export type SchengenStatus = 'safe' | 'warning' | 'danger' | 'critical';

export interface SchengenAlertSettings {
  yellowThreshold: number;                 // Days (default 60)
  redThreshold: number;                    // Days (default 80)
  emailAlerts: boolean;
  upcomingTripReminders: boolean;
}

export interface SchengenPlanningResult {
  wouldViolate: boolean;
  projectedDaysUsed: number;
  earliestSafeEntry: string | null;
  maxTripLength: number | null;
  message: string;
}

export interface SchengenFeatureStatus {
  isPremium: boolean;
  tripLimit: number | null;               // null for premium users
  tripCount: number;
  canAddTrip: boolean;
  canUsePlanning: boolean;
  canExportPdf: boolean;
  upgradeUrl: string | null;
  upgradeMessage: string | null;
}

export interface SchengenSimulationResult {
  wouldViolate: boolean;
  violations: string[];                   // Array of dates that would violate
  maxDaysUsed: number;
  proposedLength: number;
  earliestSafeDate: string | null;
  maxSafeLength: number;
  daysOverLimit: number;
}

export interface SchengenReportResponse {
  html: string;
  filename: string;
  summary: {
    daysUsed: number;
    daysRemaining: number;
    status: SchengenStatus;
    tripCount: number;
  };
}

export interface SchengenTestAlertResult {
  success: boolean;
  message: string;
  alert_level?: string;
  days_used?: number;
  thresholds?: {
    warning: number;
    danger: number;
    urgent: number;
  };
  summary?: {
    days_used: number;
    days_remaining: number;
    window_start: string;
    window_end: string;
    next_expiration: string | null;
  };
}

// ============================================
// Schengen Location Types (Phase 1)
// ============================================

export type LocationSource = 'browser' | 'manual' | 'calendar' | 'checkin';

export interface SchengenLocation {
  id: number;
  lat: number;
  lng: number;
  accuracy: number | null;
  countryCode: string | null;
  countryName: string | null;
  city: string | null;
  isSchengen: boolean;
  source: LocationSource;
  recordedAt: string;
}

export interface LocationStoreResponse {
  success: boolean;
  location: SchengenLocation;
  message: string;
}

export interface LocationHistoryResponse {
  locations: SchengenLocation[];
  total: number;
  limit: number;
  offset: number;
}

export interface LocationTodayStatus {
  hasCheckedInToday: boolean;
  todayLocations: SchengenLocation[];
  lastLocation: SchengenLocation | null;
  reminderEnabled: boolean;
  trackingEnabled: boolean;
}

export interface GeocodeResult {
  country_code: string | null;
  country_name: string | null;
  city: string | null;
  state: string | null;
  display_name: string | null;
  is_schengen: boolean;
  error?: string;
}

export interface LocationSettings {
  tracking_enabled: boolean;
  daily_reminder: boolean;
  auto_detect: boolean;
}

export interface IPDetectionResult {
  detected: boolean;
  reason: 'ip_lookup' | 'local_ip' | 'api_error' | 'lookup_failed';
  message: string;
  countryCode: string | null;
  countryName: string | null;
  city: string | null;
  isSchengen: boolean;
  ip?: string | null;
}

// ============================================
// Family Members Types
// ============================================

export interface FamilyMember {
  id: number;
  name: string;
  relationship: 'spouse' | 'child' | 'parent' | 'other';
  birthDate: string;
  nationality: string;
  visaStatus: 'pending' | 'applied' | 'approved' | 'not_required';
  documents: {
    passport: boolean;
    birthCertificate: boolean;
    marriageCertificate?: boolean;
    photos: boolean;
  };
  notes?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface FamilyMembersResponse {
  members: FamilyMember[];
  featureEnabled: boolean;
  canEdit: boolean;
}

export interface FamilyFeatureStatus {
  enabled: boolean;
  upgradeUrl: string | null;
  message: string | null;
}

// ============================================
// Calendar Sync Types (Phase 2)
// ============================================

export type CalendarProvider = 'google' | 'microsoft';
export type CalendarEventStatus = 'pending' | 'imported' | 'skipped';
export type CalendarSyncStatus = 'active' | 'error' | 'expired';

export interface CalendarProviderInfo {
  id: CalendarProvider;
  name: string;
  isConfigured: boolean;
}

export interface CalendarConnection {
  id: number;
  provider: CalendarProvider;
  providerName: string;
  calendarName: string | null;
  syncStatus: CalendarSyncStatus;
  lastSyncAt: string | null;
  createdAt: string;
}

export interface CalendarEvent {
  id: number;
  title: string;
  startDate: string;
  endDate: string;
  location: string | null;
  detectedCountry: string | null;
  countryCode: string | null;
  isSchengen: boolean;
  status: CalendarEventStatus;
  confidence: number;
  importedAsTripId: number | null;
  createdAt: string;
}

export interface CalendarSyncResult {
  synced: boolean;
  eventsFound: number;
  travelDetected: number;
  newEvents: number;
}

export interface CalendarImportResult {
  imported: number;
  skipped: number;
}

export interface CalendarICalImportResult {
  parsed: number;
  detected: number;
}

// ============================================
// CSV Import/Export Types (Phase 6)
// ============================================

export interface CSVImportResult {
  success: boolean;
  imported: number;
  skipped: number;
  errors: string[];
  message: string;
}

export interface CSVExportResult {
  csv: string;
  filename: string;
  count: number;
}

// ============================================
// Jurisdiction Types (Phase 3)
// ============================================

export type JurisdictionType = 'zone' | 'country' | 'state';
export type CountingMethod = 'rolling' | 'calendar_year' | 'fiscal_year';
export type JurisdictionStatus = 'safe' | 'warning' | 'danger' | 'critical' | 'exceeded';

export interface JurisdictionRule {
  id: number;
  code: string;
  name: string;
  type: JurisdictionType;
  parentCode: string | null;
  daysAllowed: number;
  windowDays: number;
  countingMethod: CountingMethod;
  resetMonth: number | null;
  resetDay: number | null;
  description: string | null;
  notes: string | null;
  isSystem: boolean;
}

export interface JurisdictionSummary {
  jurisdictionCode: string;
  daysUsed: number;
  daysAllowed: number;
  daysRemaining: number;
  percentage: number;
  status: JurisdictionStatus;
  windowStart: string;
  windowEnd: string;
  referenceDate: string;
  countingMethod: CountingMethod;
  nextExpiringDate: string | null;
  nextExpiringDays: number;
  tripCount: number;
  rule?: JurisdictionRule;
}

export interface MultiJurisdictionSummary {
  [jurisdictionCode: string]: JurisdictionSummary;
}

export interface TrackedJurisdictionsResponse {
  success: boolean;
  tracked: string[];
}

// ============================================
// Notification Types (Phase 5)
// ============================================

export type NotificationType =
  | 'threshold_warning'
  | 'threshold_danger'
  | 'trip_reminder'
  | 'day_expiring'
  | 'calendar_sync'
  | 'location_checkin'
  | 'test';

export type NotificationPriority = 'low' | 'normal' | 'high';

export interface NotificationItem {
  id: number;
  type: NotificationType;
  title: string;
  body: string | null;
  actionUrl: string | null;
  icon: string | null;
  priority: NotificationPriority;
  isRead: boolean;
  readAt: string | null;
  createdAt: string;
  data: Record<string, unknown> | null;
}

export interface PushStatus {
  vapidConfigured: boolean;
  subscriptions: PushSubscription[];
}

export interface PushSubscription {
  id: number;
  userAgent: string | null;
  createdAt: string;
  lastUsedAt: string | null;
}

export interface NotificationPreferences {
  email_enabled: boolean;
  push_enabled: boolean;
  threshold_warning: boolean;
  threshold_danger: boolean;
  trip_reminder: boolean;
  calendar_sync: boolean;
  location_checkin: boolean;
  quiet_hours_enabled: boolean;
  quiet_hours_start: number;
  quiet_hours_end: number;
}

// WordPress global types
declare global {
  interface Window {
    fraPortalData: {
      apiUrl: string;
      nonce: string;
      userId: number;
      siteUrl: string;
      pluginUrl: string;
      isAdmin: boolean;
    };
    PORTAL_SETTINGS?: PortalSettings;
    PORTAL_USER?: PortalUser;
    PORTAL_API?: PortalApi;
  }
}
