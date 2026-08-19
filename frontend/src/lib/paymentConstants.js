/**
 * Simulated mobile-money gateway constants. `MOBILE_NETWORKS` values must
 * match the backend's MobileNetwork enum exactly. `DECLINE_NUMBER` is the one
 * reserved fake number that always fails, mirrored on the backend in
 * ClientInvoiceController and Marketplace/ContractController.
 */

export const MOBILE_NETWORKS = [
  { value: 'airtel', label: 'Airtel Money' },
  { value: 'mixx_by_yas', label: 'Mixx by Yas' },
  { value: 'vodacom', label: 'Vodacom M-Pesa' },
  { value: 'halotel', label: 'Halotel' },
]

export const DECLINE_NUMBER = '0000000000'
