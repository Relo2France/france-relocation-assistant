import { useState } from 'react';
import { clsx } from 'clsx';
import {
  CreditCard,
  Calendar,
  CheckCircle,
  XCircle,
  AlertCircle,
  Clock,
  ArrowUpCircle,
  Loader2,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';
import {
  useMembership,
  useSubscriptions,
  usePayments,
  useCancelSubscription,
  useSuspendSubscription,
  useResumeSubscription,
  useUpgradeOptions,
} from '@/hooks/useApi';
import type { SubscriptionStatus, PaymentStatus } from '@/types';
import Modal from '@/components/shared/Modal';

interface ConfirmModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmText: string;
  isLoading?: boolean;
  isDangerous?: boolean;
}

function ConfirmModal({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmText,
  isLoading,
  isDangerous = false,
}: ConfirmModalProps) {
  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} size="sm">
      <div className="space-y-4">
        <p className="text-gray-600">{message}</p>

        <div className="flex justify-end gap-3 pt-4">
          <button
            onClick={onClose}
            disabled={isLoading}
            className="btn btn-secondary"
          >
            Cancel
          </button>
          <button
            onClick={onConfirm}
            disabled={isLoading}
            className={clsx(
              'btn flex items-center gap-2',
              isDangerous ? 'bg-red-600 hover:bg-red-700 text-white' : 'btn-primary'
            )}
          >
            {isLoading && <Loader2 className="w-4 h-4 animate-spin" />}
            {confirmText}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export default function MembershipView() {
  const { data: membership, isLoading: membershipLoading } = useMembership();
  const { data: subscriptions, isLoading: subscriptionsLoading } = useSubscriptions();
  const { data: payments, isLoading: paymentsLoading } = usePayments();
  const { data: upgradeOptions } = useUpgradeOptions();

  const cancelSubscription = useCancelSubscription();
  const suspendSubscription = useSuspendSubscription();
  const resumeSubscription = useResumeSubscription();

  const [confirmModal, setConfirmModal] = useState<{
    isOpen: boolean;
    type: 'cancel' | 'suspend' | 'resume' | null;
    subscriptionId: number | null;
  }>({
    isOpen: false,
    type: null,
    subscriptionId: null,
  });

  const [toast, setToast] = useState<{
    show: boolean;
    type: 'success' | 'error';
    message: string;
  }>({
    show: false,
    type: 'success',
    message: '',
  });

  // Pagination state for payments
  const [currentPage, setCurrentPage] = useState(1);
  const paymentsPerPage = 10;
  const totalPages = Math.ceil((payments?.length || 0) / paymentsPerPage);
  const paginatedPayments = payments?.slice(
    (currentPage - 1) * paymentsPerPage,
    currentPage * paymentsPerPage
  );

  const showToast = (type: 'success' | 'error', message: string) => {
    setToast({ show: true, type, message });
    setTimeout(() => {
      setToast({ show: false, type: 'success', message: '' });
    }, 5000);
  };

  const handleConfirm = async () => {
    if (!confirmModal.subscriptionId || !confirmModal.type) return;

    try {
      if (confirmModal.type === 'cancel') {
        await cancelSubscription.mutateAsync(confirmModal.subscriptionId);
        showToast('success', 'Subscription cancelled successfully');
      } else if (confirmModal.type === 'suspend') {
        await suspendSubscription.mutateAsync(confirmModal.subscriptionId);
        showToast('success', 'Subscription suspended successfully');
      } else if (confirmModal.type === 'resume') {
        await resumeSubscription.mutateAsync(confirmModal.subscriptionId);
        showToast('success', 'Subscription resumed successfully');
      }
      setConfirmModal({ isOpen: false, type: null, subscriptionId: null });
    } catch (error) {
      showToast('error', 'Action failed. Please try again.');
    }
  };

  const getStatusBadge = (status: SubscriptionStatus) => {
    const styles = {
      active: 'bg-green-100 text-green-700',
      suspended: 'bg-yellow-100 text-yellow-700',
      cancelled: 'bg-red-100 text-red-700',
      expired: 'bg-gray-100 text-gray-700',
      pending: 'bg-blue-100 text-blue-700',
    };

    const icons = {
      active: CheckCircle,
      suspended: Clock,
      cancelled: XCircle,
      expired: AlertCircle,
      pending: Clock,
    };

    const Icon = icons[status];

    return (
      <span className={clsx('inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', styles[status])}>
        <Icon className="w-3.5 h-3.5" />
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  const getPaymentStatusBadge = (status: PaymentStatus) => {
    const styles = {
      complete: 'bg-green-100 text-green-700',
      pending: 'bg-yellow-100 text-yellow-700',
      failed: 'bg-red-100 text-red-700',
      refunded: 'bg-gray-100 text-gray-700',
    };

    return (
      <span className={clsx('inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium', styles[status])}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  if (membershipLoading) {
    return <LoadingSkeleton />;
  }

  const primarySubscription = subscriptions?.[0];
  const hasUpgradeOptions = upgradeOptions && upgradeOptions.length > 0;

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Membership</h1>
        <p className="text-gray-600 mt-1">
          Manage your subscription and payment information
        </p>
      </div>

      {/* Toast notification */}
      {toast.show && (
        <div
          className={clsx(
            'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg animate-in slide-in-from-top-5 duration-300',
            toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
          )}
        >
          <div className="flex items-center gap-2">
            {toast.type === 'success' ? (
              <CheckCircle className="w-5 h-5" />
            ) : (
              <XCircle className="w-5 h-5" />
            )}
            <span className="font-medium">{toast.message}</span>
          </div>
        </div>
      )}

      <div className="space-y-6">
        {/* Membership Status Card */}
        <div className="card p-6">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <CreditCard className="w-6 h-6 text-primary-600" />
                <h2 className="text-xl font-semibold text-gray-900">
                  {membership?.membership_level || 'No Active Membership'}
                </h2>
              </div>

              <div className="mt-4 space-y-2">
                <div className="flex items-center gap-2">
                  <span className="text-gray-600">Status:</span>
                  {primarySubscription ? (
                    getStatusBadge(primarySubscription.status)
                  ) : (
                    <span className="text-gray-500">Inactive</span>
                  )}
                </div>

                {primarySubscription?.expires_at && (
                  <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-sm text-gray-600">
                      {primarySubscription.status === 'active' ? 'Renews' : 'Expires'} on:{' '}
                      {new Date(primarySubscription.expires_at).toLocaleDateString()}
                    </span>
                  </div>
                )}
              </div>
            </div>

            {primarySubscription?.can_upgrade && hasUpgradeOptions && (
              <button className="btn btn-primary flex items-center gap-2">
                <ArrowUpCircle className="w-4 h-4" />
                Upgrade
              </button>
            )}
          </div>
        </div>

        {/* Active Subscriptions Section */}
        {subscriptions && subscriptions.length > 0 && (
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Active Subscriptions</h2>

            <div className="overflow-x-auto">
              {/* Desktop Table View */}
              <table className="hidden md:table min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Membership
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Price
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Next Billing
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {subscriptions.map((subscription) => (
                    <tr key={subscription.id} className="hover:bg-gray-50">
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="font-medium text-gray-900">{subscription.membership_title}</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{subscription.price}</div>
                        <div className="text-xs text-gray-500">{subscription.billing_period}</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        {getStatusBadge(subscription.status)}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                        {subscription.next_billing_date
                          ? new Date(subscription.next_billing_date).toLocaleDateString()
                          : 'N/A'}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-2">
                          {subscription.can_resume && (
                            <button
                              onClick={() =>
                                setConfirmModal({
                                  isOpen: true,
                                  type: 'resume',
                                  subscriptionId: subscription.id,
                                })
                              }
                              className="text-sm text-primary-600 hover:text-primary-700 font-medium"
                            >
                              Resume
                            </button>
                          )}
                          {subscription.can_suspend && (
                            <button
                              onClick={() =>
                                setConfirmModal({
                                  isOpen: true,
                                  type: 'suspend',
                                  subscriptionId: subscription.id,
                                })
                              }
                              className="text-sm text-yellow-600 hover:text-yellow-700 font-medium"
                            >
                              Suspend
                            </button>
                          )}
                          {subscription.can_cancel && (
                            <button
                              onClick={() =>
                                setConfirmModal({
                                  isOpen: true,
                                  type: 'cancel',
                                  subscriptionId: subscription.id,
                                })
                              }
                              className="text-sm text-red-600 hover:text-red-700 font-medium"
                            >
                              Cancel
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Mobile Card View */}
              <div className="md:hidden space-y-4">
                {subscriptions.map((subscription) => (
                  <div key={subscription.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between mb-3">
                      <div>
                        <div className="font-medium text-gray-900">{subscription.membership_title}</div>
                        <div className="text-sm text-gray-600 mt-1">
                          {subscription.price} / {subscription.billing_period}
                        </div>
                      </div>
                      {getStatusBadge(subscription.status)}
                    </div>

                    {subscription.next_billing_date && (
                      <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                        <Calendar className="w-4 h-4" />
                        Next billing: {new Date(subscription.next_billing_date).toLocaleDateString()}
                      </div>
                    )}

                    <div className="flex items-center gap-2 pt-3 border-t border-gray-200">
                      {subscription.can_resume && (
                        <button
                          onClick={() =>
                            setConfirmModal({
                              isOpen: true,
                              type: 'resume',
                              subscriptionId: subscription.id,
                            })
                          }
                          className="text-sm text-primary-600 hover:text-primary-700 font-medium"
                        >
                          Resume
                        </button>
                      )}
                      {subscription.can_suspend && (
                        <button
                          onClick={() =>
                            setConfirmModal({
                              isOpen: true,
                              type: 'suspend',
                              subscriptionId: subscription.id,
                            })
                          }
                          className="text-sm text-yellow-600 hover:text-yellow-700 font-medium"
                        >
                          Suspend
                        </button>
                      )}
                      {subscription.can_cancel && (
                        <button
                          onClick={() =>
                            setConfirmModal({
                              isOpen: true,
                              type: 'cancel',
                              subscriptionId: subscription.id,
                            })
                          }
                          className="text-sm text-red-600 hover:text-red-700 font-medium"
                        >
                          Cancel
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Empty state for no subscriptions */}
        {(!subscriptions || subscriptions.length === 0) && !subscriptionsLoading && (
          <div className="card p-12 text-center">
            <CreditCard className="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No Active Subscriptions</h3>
            <p className="text-gray-600 mb-6">
              You don't have any active subscriptions at the moment.
            </p>
            {hasUpgradeOptions && (
              <button className="btn btn-primary">
                View Membership Plans
              </button>
            )}
          </div>
        )}

        {/* Payment History Section */}
        {payments && payments.length > 0 && (
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Payment History</h2>

            <div className="overflow-x-auto">
              {/* Desktop Table View */}
              <table className="hidden md:table min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Amount
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Payment Method
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Transaction ID
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {paginatedPayments?.map((payment) => (
                    <tr key={payment.id} className="hover:bg-gray-50">
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        {new Date(payment.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {payment.amount}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        {getPaymentStatusBadge(payment.status)}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                        {payment.payment_method}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                        {payment.transaction_id}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Mobile Card View */}
              <div className="md:hidden space-y-4">
                {paginatedPayments?.map((payment) => (
                  <div key={payment.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between mb-3">
                      <div>
                        <div className="font-medium text-gray-900">{payment.amount}</div>
                        <div className="text-sm text-gray-600 mt-1">
                          {new Date(payment.created_at).toLocaleDateString()}
                        </div>
                      </div>
                      {getPaymentStatusBadge(payment.status)}
                    </div>

                    <div className="space-y-1 text-sm text-gray-600">
                      <div>Payment method: {payment.payment_method}</div>
                      <div className="font-mono text-xs text-gray-500">
                        ID: {payment.transaction_id}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div className="text-sm text-gray-600">
                  Page {currentPage} of {totalPages}
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setCurrentPage(currentPage - 1)}
                    disabled={currentPage === 1}
                    className="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>
                  <button
                    onClick={() => setCurrentPage(currentPage + 1)}
                    disabled={currentPage === totalPages}
                    className="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Empty state for no payments */}
        {(!payments || payments.length === 0) && !paymentsLoading && (
          <div className="card p-12 text-center">
            <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No Payment History</h3>
            <p className="text-gray-600">
              Your payment transactions will appear here.
            </p>
          </div>
        )}

        {/* Upgrade Options */}
        {hasUpgradeOptions && primarySubscription?.can_upgrade && (
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Upgrade Options</h2>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {upgradeOptions.map((option) => (
                <div key={option.id} className="border border-gray-200 rounded-lg p-6 hover:border-primary-300 hover:shadow-md transition-all">
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">{option.title}</h3>
                  <div className="text-3xl font-bold text-primary-600 mb-4">{option.price}</div>

                  <ul className="space-y-2 mb-6">
                    {option.features.map((feature, index) => (
                      <li key={index} className="flex items-start gap-2 text-sm text-gray-600">
                        <CheckCircle className="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>

                  <button className="btn btn-primary w-full flex items-center justify-center gap-2">
                    <ArrowUpCircle className="w-4 h-4" />
                    Upgrade Now
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Confirmation Modal */}
      <ConfirmModal
        isOpen={confirmModal.isOpen}
        onClose={() => setConfirmModal({ isOpen: false, type: null, subscriptionId: null })}
        onConfirm={handleConfirm}
        title={
          confirmModal.type === 'cancel'
            ? 'Cancel Subscription'
            : confirmModal.type === 'suspend'
            ? 'Suspend Subscription'
            : 'Resume Subscription'
        }
        message={
          confirmModal.type === 'cancel'
            ? 'Are you sure you want to cancel this subscription? You will lose access to premium features at the end of your current billing period.'
            : confirmModal.type === 'suspend'
            ? 'Are you sure you want to suspend this subscription? Your subscription will be paused and you can resume it later.'
            : 'Are you sure you want to resume this subscription? Your next billing will be scheduled according to your billing period.'
        }
        confirmText={
          confirmModal.type === 'cancel'
            ? 'Cancel Subscription'
            : confirmModal.type === 'suspend'
            ? 'Suspend'
            : 'Resume'
        }
        isLoading={
          cancelSubscription.isPending ||
          suspendSubscription.isPending ||
          resumeSubscription.isPending
        }
        isDangerous={confirmModal.type === 'cancel'}
      />
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="p-6 max-w-7xl mx-auto">
      <div className="mb-6">
        <div className="h-8 w-48 bg-gray-200 rounded animate-pulse mb-2" />
        <div className="h-4 w-64 bg-gray-200 rounded animate-pulse" />
      </div>

      <div className="space-y-6">
        <div className="card p-6">
          <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-4" />
          <div className="h-4 w-32 bg-gray-200 rounded animate-pulse" />
        </div>

        <div className="card p-6">
          <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-6" />
          <div className="space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-16 bg-gray-200 rounded animate-pulse" />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
