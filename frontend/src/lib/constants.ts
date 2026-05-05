// Shared order status constants used across the admin dashboard
export const ORDER_STATUSES = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'] as const;

export type OrderStatus = typeof ORDER_STATUSES[number];

export const statusBadge: Record<string, string> = {
  Pending: 'bg-amber-100 text-amber-700',
  Approved: 'bg-teal-100 text-teal-700',
  Invoiced: 'bg-blue-100 text-blue-700',
  Fulfilled: 'bg-green-100 text-green-700',
  Cancelled: 'bg-red-100 text-red-700',
};

export const statusDot: Record<string, string> = {
  Pending: 'bg-amber-500',
  Approved: 'bg-teal-500',
  Invoiced: 'bg-blue-500',
  Fulfilled: 'bg-green-500',
  Cancelled: 'bg-red-500',
};
