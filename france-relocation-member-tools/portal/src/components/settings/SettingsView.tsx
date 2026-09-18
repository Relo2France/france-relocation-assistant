import { useEffect, useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  Bell,
  Check,
  Loader2,
  RefreshCw,
  Save,
  Trash2,
  User,
} from 'lucide-react';
import { HttpError } from '@/api/client';
import {
  useCurrentUser,
  useDashboard,
  useDeleteAccount,
  useResetProfile,
  useUpdateProfile,
  useUpdateSettings,
  useUserSettings,
} from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type {UserSettings } from '@/types';

type SettingsTab = 'portal-account' | 'notifications';

// Icon mapping for menu items

// Field type for Visa Profile sections


export default function SettingsView() {
  const { settingsTab, setSettingsTab } = usePortalStore();
  const [activeTab, setActiveTab] = useState<SettingsTab>('portal-account');

  // Handle navigation from other components (e.g., Dashboard)
  useEffect(() => {
    if (settingsTab && ['portal-account', 'notifications'].includes(settingsTab)) {
      setActiveTab(settingsTab as SettingsTab);
      setSettingsTab(null); // Clear after using
    }
  }, [settingsTab, setSettingsTab]);

  const tabs = [
    { id: 'portal-account' as SettingsTab, label: 'Portal Account', icon: User },
    { id: 'notifications' as SettingsTab, label: 'Notifications', icon: Bell },
  ];

  return (
    <div className="p-6">
      {/* The title is in the top bar; one line under it. */}
      <p className="text-gray-600 mb-6 max-w-[64ch]">Your sign-in, your account and how we reach you. Your visa details live in Profile.</p>

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
          {activeTab === 'portal-account' && <PortalAccountSection />}
          {/* Visa and personal details live in one place: Profile. */}
          {activeTab === 'notifications' && <NotificationsSection />}
        </div>
      </div>
    </div>
  );
}

function PortalAccountSection() {
  const { data: user, isLoading: userLoading } = useCurrentUser();
  const updateProfile = useUpdateProfile();
  const deleteAccount = useDeleteAccount();
  const resetProfile = useResetProfile();
  // A partner works on the owner's file; only the account holder can delete it.
  const { data: dashboard } = useDashboard();
  const isPartner = dashboard?.household?.role === 'partner';

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    display_name: '',
  });
  const [initialized, setInitialized] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [deleteConfirmText, setDeleteConfirmText] = useState('');
  const [showResetConfirm, setShowResetConfirm] = useState(false);
  const [resetConfirmText, setResetConfirmText] = useState('');
  const [resetSuccess, setResetSuccess] = useState(false);

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

  const handleDeleteAccount = () => {
    deleteAccount.mutate(deleteConfirmText, {
      onSuccess: (result) => {
        // The server says where a signed-out member lands.
        window.location.href = result.redirect || window.fraPortalData?.siteUrl || '/';
      },
    });
  };

  const handleResetProfile = () => {
    resetProfile.mutate(resetConfirmText, {
      onSuccess: () => {
        setResetSuccess(true);
        setShowResetConfirm(false);
        setResetConfirmText('');
        // Reload the page after a brief delay to show fresh state
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      },
    });
  };

  const isDeleteEnabled = deleteConfirmText === 'DELETE MY ACCOUNT';
  const isResetEnabled = resetConfirmText === 'RESET MY PROFILE';

  if (userLoading) {
    return <SettingsSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Profile Information card */}
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

        {/* Reset Profile Section */}
        <div className="p-4 bg-amber-50 rounded-lg mb-4">
          <div className="flex items-start gap-3">
            <RefreshCw className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-medium text-gray-900">Reset Visa Profile</h3>
              <p className="text-sm text-gray-600 mt-1">
                Reset all your profile data, tasks, documents, and progress. Your account will remain active, but it will be as if you&apos;re starting fresh as a new member.
              </p>

              {resetSuccess ? (
                <div className="mt-4 p-3 bg-green-100 border border-green-200 rounded-lg flex items-center gap-2">
                  <Check className="w-5 h-5 text-green-600" />
                  <p className="text-sm font-medium text-green-800">
                    Profile reset successfully! Reloading page...
                  </p>
                </div>
              ) : !showResetConfirm ? (
                <button
                  type="button"
                  onClick={() => setShowResetConfirm(true)}
                  className="mt-4 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors flex items-center gap-2"
                >
                  <RefreshCw className="w-4 h-4" />
                  Reset My Profile
                </button>
              ) : (
                <div className="mt-4 space-y-4">
                  <div className="p-3 bg-amber-100 border border-amber-200 rounded-lg">
                    <p className="text-sm font-medium text-amber-800">
                      This will delete all your tasks, documents, notes, profile information, and progress. Your account will remain active but all relocation data will be removed.
                    </p>
                  </div>

                  <div>
                    <label htmlFor="reset-confirm" className="block text-sm font-medium text-gray-700 mb-1">
                      Type <span className="font-mono bg-gray-100 px-1 rounded">RESET MY PROFILE</span> to confirm:
                    </label>
                    <input
                      type="text"
                      id="reset-confirm"
                      value={resetConfirmText}
                      onChange={(e) => setResetConfirmText(e.target.value)}
                      placeholder="RESET MY PROFILE"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                      autoComplete="off"
                    />
                  </div>

                  <div className="flex items-center gap-3">
                    <button
                      type="button"
                      onClick={handleResetProfile}
                      disabled={!isResetEnabled || resetProfile.isPending}
                      className={clsx(
                        'px-4 py-2 text-sm font-medium rounded-lg flex items-center gap-2 transition-colors',
                        isResetEnabled && !resetProfile.isPending
                          ? 'bg-amber-600 text-white hover:bg-amber-700'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      )}
                    >
                      {resetProfile.isPending ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin" />
                          Resetting...
                        </>
                      ) : (
                        <>
                          <RefreshCw className="w-4 h-4" />
                          Reset Profile
                        </>
                      )}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setShowResetConfirm(false);
                        setResetConfirmText('');
                      }}
                      className="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors"
                    >
                      Cancel
                    </button>
                  </div>

                  {resetProfile.isError && (
                    <p className="text-sm text-red-600">
                      {resetProfile.error instanceof Error
                        ? resetProfile.error.message
                        : 'Failed to reset profile. Please try again or contact support.'}
                    </p>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Delete Account Section: the account holder only */}
        {!isPartner && (
        <div className="p-4 bg-red-50 rounded-lg">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-medium text-gray-900">Delete Account</h3>
              <p className="text-sm text-gray-600 mt-1">
                Once you delete your account, there is no going back. All your data, documents, tasks, and profile information will be permanently removed.
              </p>

              {!showDeleteConfirm ? (
                <button
                  type="button"
                  onClick={() => setShowDeleteConfirm(true)}
                  className="mt-4 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                >
                  <Trash2 className="w-4 h-4" />
                  Delete My Account
                </button>
              ) : (
                <div className="mt-4 space-y-4">
                  <div className="p-3 bg-red-100 border border-red-200 rounded-lg">
                    <p className="text-sm font-medium text-red-800">
                      This action cannot be undone. This will permanently delete your account and remove all associated data.
                    </p>
                  </div>

                  <div>
                    <label htmlFor="delete-confirm" className="block text-sm font-medium text-gray-700 mb-1">
                      Type <span className="font-mono bg-gray-100 px-1 rounded">DELETE MY ACCOUNT</span> to confirm:
                    </label>
                    <input
                      type="text"
                      id="delete-confirm"
                      value={deleteConfirmText}
                      onChange={(e) => setDeleteConfirmText(e.target.value)}
                      placeholder="DELETE MY ACCOUNT"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                      autoComplete="off"
                    />
                  </div>

                  <div className="flex items-center gap-3">
                    <button
                      type="button"
                      onClick={handleDeleteAccount}
                      disabled={!isDeleteEnabled || deleteAccount.isPending}
                      className={clsx(
                        'px-4 py-2 text-sm font-medium rounded-lg flex items-center gap-2 transition-colors',
                        isDeleteEnabled && !deleteAccount.isPending
                          ? 'bg-red-600 text-white hover:bg-red-700'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      )}
                    >
                      {deleteAccount.isPending ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin" />
                          Deleting...
                        </>
                      ) : (
                        <>
                          <Trash2 className="w-4 h-4" />
                          Permanently Delete Account
                        </>
                      )}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setShowDeleteConfirm(false);
                        setDeleteConfirmText('');
                      }}
                      className="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors"
                    >
                      Cancel
                    </button>
                  </div>

                  {deleteAccount.isError && (
                    <p className="text-sm text-red-600" role="alert">
                      {deleteAccount.error instanceof HttpError && deleteAccount.error.status === 403
                        ? deleteAccount.error.message || 'Only the account holder can delete the household file.'
                        : deleteAccount.error instanceof Error
                          ? deleteAccount.error.message
                          : 'Failed to delete account. Please try again or contact support.'}
                    </p>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
        )}
      </div>
    </div>
  );
}

// Visa Profile Section with all relocation fields
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
      label: 'Email updates',
      description: 'An email when we write to you, answer your support request, or finish a report. Off, and everything still arrives in Messages; the reminders and digest below stop too.',
    },
    {
      key: 'task_reminders' as keyof UserSettings,
      label: 'Step reminders',
      description: 'Once you start a stage, a short email about your next steps there: a week before one is due, the day before, and once if the date passes. Stages you have not started stay quiet. It goes to whoever the step is assigned to.',
    },
    {
      key: 'weekly_digest' as keyof UserSettings,
      label: 'Weekly digest',
      description: 'Monday morning: what you finished last week and your next steps.',
    },
  ];

  return (
    <div className="card p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-1">Emails</h2>
      <p className="text-sm text-gray-500 mb-6 max-w-[65ch]">These apply to your whole household. Invitations and password emails are always sent.</p>

      <div className="space-y-6">
        {toggles.map((toggle) => (
          <div key={toggle.key} className="flex items-center justify-between gap-4">
            <div className="min-w-0 max-w-[65ch]">
              <h3 id={`switch-${toggle.key}`} className="font-medium text-gray-900">{toggle.label}</h3>
              <p id={`switch-${toggle.key}-desc`} className="text-sm text-gray-500">{toggle.description}</p>
            </div>
            <button
              type="button"
              role="switch"
              aria-checked={!!settings?.[toggle.key]}
              aria-labelledby={`switch-${toggle.key}`}
              aria-describedby={`switch-${toggle.key}-desc`}
              disabled={toggle.key !== 'email_notifications' && !settings?.email_notifications}
              onClick={() => handleToggle(toggle.key, !settings?.[toggle.key])}
              className={clsx(
                'disabled:opacity-50 disabled:cursor-not-allowed',
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
