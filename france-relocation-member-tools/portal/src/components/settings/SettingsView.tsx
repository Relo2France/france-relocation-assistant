import { useState } from 'react';
import { clsx } from 'clsx';
import {
  User,
  Bell,
  Shield,
  Save,
  Check,
  Loader2,
} from 'lucide-react';
import {
  useCurrentUser,
  useUpdateProfile,
  useUserSettings,
  useUpdateSettings,
} from '@/hooks/useApi';
import type { UserSettings } from '@/types';

type SettingsTab = 'profile' | 'notifications' | 'account';

export default function SettingsView() {
  const [activeTab, setActiveTab] = useState<SettingsTab>('profile');

  const tabs = [
    { id: 'profile' as SettingsTab, label: 'Profile', icon: User },
    { id: 'notifications' as SettingsTab, label: 'Notifications', icon: Bell },
    { id: 'account' as SettingsTab, label: 'Account', icon: Shield },
  ];

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-600 mt-1">
          Manage your profile and preferences
        </p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Sidebar navigation */}
        <div className="lg:w-64 flex-shrink-0">
          <nav className="card p-2">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={clsx(
                    'w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors',
                    activeTab === tab.id
                      ? 'bg-primary-50 text-primary-700'
                      : 'text-gray-600 hover:bg-gray-50'
                  )}
                >
                  <Icon className="w-5 h-5" />
                  <span className="font-medium">{tab.label}</span>
                </button>
              );
            })}
          </nav>
        </div>

        {/* Content area */}
        <div className="flex-1">
          {activeTab === 'profile' && <ProfileSection />}
          {activeTab === 'notifications' && <NotificationsSection />}
          {activeTab === 'account' && <AccountSection />}
        </div>
      </div>
    </div>
  );
}

function ProfileSection() {
  const { data: user, isLoading: userLoading } = useCurrentUser();
  const updateProfile = useUpdateProfile();

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    display_name: '',
  });
  const [initialized, setInitialized] = useState(false);

  // Initialize form when user data loads
  if (user && !initialized) {
    setFormData({
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      display_name: user.display_name || '',
    });
    setInitialized(true);
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  if (userLoading) {
    return <SettingsSkeleton />;
  }

  return (
    <div className="card p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-6">Profile Information</h2>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Avatar */}
        <div className="flex items-center gap-4">
          <img
            src={user?.avatar_url}
            alt={user?.display_name}
            className="w-20 h-20 rounded-full"
          />
          <div>
            <p className="text-sm text-gray-600">
              Profile photo is managed through your Gravatar account
            </p>
            <a
              href="https://gravatar.com"
              target="_blank"
              rel="noopener noreferrer"
              className="text-sm text-primary-600 hover:text-primary-700"
            >
              Change on Gravatar
            </a>
          </div>
        </div>

        {/* Name fields */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 mb-1">
              First Name
            </label>
            <input
              type="text"
              id="first_name"
              value={formData.first_name}
              onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <div>
            <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 mb-1">
              Last Name
            </label>
            <input
              type="text"
              id="last_name"
              value={formData.last_name}
              onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
        </div>

        <div>
          <label htmlFor="display_name" className="block text-sm font-medium text-gray-700 mb-1">
            Display Name
          </label>
          <input
            type="text"
            id="display_name"
            value={formData.display_name}
            onChange={(e) => setFormData({ ...formData, display_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
          <p className="mt-1 text-sm text-gray-500">
            This is how your name will appear throughout the portal
          </p>
        </div>

        {/* Email (read-only) */}
        <div>
          <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
            Email Address
          </label>
          <input
            type="email"
            id="email"
            value={user?.email || ''}
            disabled
            className="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
          />
          <p className="mt-1 text-sm text-gray-500">
            Contact support to change your email address
          </p>
        </div>

        {/* Submit button */}
        <div className="flex items-center gap-3">
          <button
            type="submit"
            disabled={updateProfile.isPending}
            className="btn btn-primary flex items-center gap-2"
          >
            {updateProfile.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : updateProfile.isSuccess ? (
              <Check className="w-4 h-4" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            {updateProfile.isPending ? 'Saving...' : updateProfile.isSuccess ? 'Saved!' : 'Save Changes'}
          </button>
          {updateProfile.isError && (
            <span className="text-sm text-red-600">
              Failed to save. Please try again.
            </span>
          )}
        </div>
      </form>
    </div>
  );
}

function NotificationsSection() {
  const { data: settings, isLoading } = useUserSettings();
  const updateSettings = useUpdateSettings();

  const handleToggle = (key: keyof UserSettings, value: boolean) => {
    updateSettings.mutate({ [key]: value });
  };

  if (isLoading) {
    return <SettingsSkeleton />;
  }

  const toggles = [
    {
      key: 'email_notifications' as keyof UserSettings,
      label: 'Email Notifications',
      description: 'Receive email updates about your relocation progress',
    },
    {
      key: 'task_reminders' as keyof UserSettings,
      label: 'Task Reminders',
      description: 'Get reminded about upcoming and overdue tasks',
    },
    {
      key: 'weekly_digest' as keyof UserSettings,
      label: 'Weekly Digest',
      description: 'Receive a weekly summary of your progress',
    },
  ];

  return (
    <div className="card p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-6">Notification Preferences</h2>

      <div className="space-y-6">
        {toggles.map((toggle) => (
          <div key={toggle.key} className="flex items-center justify-between">
            <div>
              <h3 className="font-medium text-gray-900">{toggle.label}</h3>
              <p className="text-sm text-gray-500">{toggle.description}</p>
            </div>
            <button
              type="button"
              onClick={() => handleToggle(toggle.key, !settings?.[toggle.key])}
              className={clsx(
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                settings?.[toggle.key] ? 'bg-primary-600' : 'bg-gray-200'
              )}
            >
              <span
                className={clsx(
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  settings?.[toggle.key] ? 'translate-x-5' : 'translate-x-0'
                )}
              />
            </button>
          </div>
        ))}
      </div>

      {/* Language preference */}
      <div className="mt-8 pt-6 border-t border-gray-200">
        <h3 className="font-medium text-gray-900 mb-4">Regional Settings</h3>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="language" className="block text-sm font-medium text-gray-700 mb-1">
              Language
            </label>
            <select
              id="language"
              value={settings?.language || 'en'}
              onChange={(e) => updateSettings.mutate({ language: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="en">English</option>
              <option value="fr">Fran&#231;ais</option>
            </select>
          </div>

          <div>
            <label htmlFor="timezone" className="block text-sm font-medium text-gray-700 mb-1">
              Timezone
            </label>
            <select
              id="timezone"
              value={settings?.timezone || 'Europe/Paris'}
              onChange={(e) => updateSettings.mutate({ timezone: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="Europe/Paris">Paris (CET)</option>
              <option value="Europe/London">London (GMT)</option>
              <option value="America/New_York">New York (EST)</option>
              <option value="America/Los_Angeles">Los Angeles (PST)</option>
              <option value="Asia/Tokyo">Tokyo (JST)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

function AccountSection() {
  const { data: user, isLoading } = useCurrentUser();

  if (isLoading) {
    return <SettingsSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Account info card */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Account Information</h2>

        <div className="space-y-4">
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Username</span>
            <span className="font-medium text-gray-900">{user?.username}</span>
          </div>
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Email</span>
            <span className="font-medium text-gray-900">{user?.email}</span>
          </div>
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Account Type</span>
            <span className="font-medium text-gray-900">
              {user?.is_admin ? 'Administrator' : 'Member'}
            </span>
          </div>
          <div className="flex justify-between py-3">
            <span className="text-gray-600">Membership Status</span>
            <span className={clsx(
              'px-2 py-1 rounded-full text-xs font-medium',
              user?.is_member
                ? 'bg-green-100 text-green-700'
                : 'bg-gray-100 text-gray-700'
            )}>
              {user?.is_member ? 'Active' : 'Inactive'}
            </span>
          </div>
        </div>
      </div>

      {/* Security card */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Security</h2>

        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <h3 className="font-medium text-gray-900">Password</h3>
              <p className="text-sm text-gray-500">Last changed: Unknown</p>
            </div>
            <a
              href={`${window.fraPortalData?.siteUrl || ''}/wp-admin/profile.php`}
              className="btn btn-secondary text-sm"
            >
              Change Password
            </a>
          </div>
        </div>
      </div>

      {/* Danger zone */}
      <div className="card p-6 border-red-200">
        <h2 className="text-lg font-semibold text-red-600 mb-4">Danger Zone</h2>

        <div className="p-4 bg-red-50 rounded-lg">
          <h3 className="font-medium text-gray-900">Delete Account</h3>
          <p className="text-sm text-gray-600 mt-1">
            Once you delete your account, there is no going back. All your data will be permanently removed.
          </p>
          <p className="text-sm text-gray-600 mt-2">
            To delete your account, please contact support.
          </p>
        </div>
      </div>
    </div>
  );
}

function SettingsSkeleton() {
  return (
    <div className="card p-6">
      <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-6" />
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-12 bg-gray-200 rounded animate-pulse" />
        ))}
      </div>
    </div>
  );
}
