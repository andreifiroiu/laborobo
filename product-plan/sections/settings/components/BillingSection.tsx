import type { BillingInfo, Invoice } from '@/../product/sections/settings/types'

interface BillingSectionProps {
  billingInfo: BillingInfo
  invoices: Invoice[]
  onDownloadInvoice?: (invoiceId: string) => void
  onUpdatePaymentMethod?: () => void
  onChangePlan?: () => void
}

export function BillingSection({
  billingInfo,
  invoices,
  onDownloadInvoice,
  onUpdatePaymentMethod,
  onChangePlan,
}: BillingSectionProps) {
  return (
    <div className="max-w-6xl mx-auto p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2">Billing</h2>
        <p className="text-slate-600 dark:text-slate-400">
          Manage your subscription and billing information
        </p>
      </div>

      {/* Current Plan */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 mb-6">
        <div className="flex items-start justify-between mb-6">
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-1">
              {billingInfo.plan} Plan
            </h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">
              ${billingInfo.price}/month • Next billing: {new Date(billingInfo.nextBillingDate).toLocaleDateString()}
            </p>
          </div>
          <button
            onClick={onChangePlan}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            Change Plan
          </button>
        </div>

        <div className="grid grid-cols-4 gap-6">
          {Object.entries(billingInfo.usage).map(([key, usage]) => (
            <div key={key}>
              <p className="text-sm text-slate-600 dark:text-slate-400 mb-1 capitalize">
                {key.replace(/([A-Z])/g, ' $1').trim()}
              </p>
              <p className="text-xl font-semibold text-slate-900 dark:text-slate-50">
                {usage.current} / {usage.included}
                {usage.unit ? ` ${usage.unit}` : ''}
              </p>
            </div>
          ))}
        </div>
      </div>

      {/* Payment Method */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-1">
              Payment Method
            </h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">
              {billingInfo.paymentMethod.brand} ending in {billingInfo.paymentMethod.last4} • Expires{' '}
              {billingInfo.paymentMethod.expiryMonth}/{billingInfo.paymentMethod.expiryYear}
            </p>
          </div>
          <button
            onClick={onUpdatePaymentMethod}
            className="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-50 text-sm font-medium rounded-lg transition-colors"
          >
            Update
          </button>
        </div>
      </div>

      {/* Invoices */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-50">Invoices</h3>
        </div>
        <table className="min-w-full">
          <thead className="bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Invoice
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Date
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Amount
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Status
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {invoices.map((invoice) => (
              <tr key={invoice.id}>
                <td className="px-6 py-4 text-sm font-medium text-slate-900 dark:text-slate-50">
                  {invoice.invoiceNumber}
                </td>
                <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                  {new Date(invoice.date).toLocaleDateString()}
                </td>
                <td className="px-6 py-4 text-sm text-slate-900 dark:text-slate-50">
                  ${invoice.amount.toFixed(2)}
                </td>
                <td className="px-6 py-4">
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {invoice.status}
                  </span>
                </td>
                <td className="px-6 py-4 text-right">
                  <button
                    onClick={() => onDownloadInvoice?.(invoice.id)}
                    className="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
                  >
                    Download
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
