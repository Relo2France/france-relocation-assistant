/**
 * Hook to determine if virtualization should be used
 * Only virtualize when there are enough items to benefit from it
 */
export function useVirtualization(itemCount: number, threshold = 50): boolean {
  return itemCount > threshold;
}
