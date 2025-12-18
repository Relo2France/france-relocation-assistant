/**
 * ProfileSkeleton Component
 *
 * Loading skeleton for the profile page.
 */

export default function ProfileSkeleton() {
  return (
    <div className="p-6" aria-busy="true" aria-label="Loading profile">
      <div className="mb-6">
        <div className="h-8 w-48 bg-gray-200 rounded animate-pulse mb-2" />
        <div className="h-4 w-96 bg-gray-200 rounded animate-pulse" />
      </div>

      <div className="card p-6 mb-6">
        <div className="h-6 w-64 bg-gray-200 rounded animate-pulse mb-4" />
        <div className="h-3 w-full bg-gray-200 rounded animate-pulse" />
      </div>

      <div className="space-y-4">
        {[1, 2, 3, 4, 5].map((i) => (
          <div key={i} className="card p-6">
            <div className="h-6 w-48 bg-gray-200 rounded animate-pulse" />
          </div>
        ))}
      </div>
    </div>
  );
}
