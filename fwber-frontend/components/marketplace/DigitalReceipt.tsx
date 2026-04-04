'use client'

import { X, ReceiptText, Store, Ticket } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface DigitalReceiptProps {
  id: string | number
  itemName: string
  price: string | number
  merchantName: string
  timestamp: string
  redemptionCode?: string
  onClose: () => void
}

export function DigitalReceipt({
  id,
  itemName,
  price,
  merchantName,
  timestamp,
  redemptionCode,
  onClose,
}: DigitalReceiptProps) {
  return (
    <div className="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 px-4">
      <div className="w-full max-w-md rounded-3xl border border-emerald-200 bg-white p-6 shadow-2xl dark:border-emerald-900/40 dark:bg-gray-900">
        <div className="mb-4 flex items-start justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
              <ReceiptText className="h-3.5 w-3.5" />
              Digital Receipt
            </div>
            <h3 className="mt-3 text-2xl font-black text-gray-900 dark:text-white">Purchase confirmed</h3>
          </div>
          <button onClick={onClose} className="rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="space-y-4 rounded-2xl border border-dashed border-gray-300 p-4 dark:border-gray-700">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Receipt ID</div>
            <div className="font-mono text-sm text-gray-900 dark:text-white">{id}</div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Item</div>
            <div className="text-lg font-bold text-gray-900 dark:text-white">{itemName}</div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Merchant</div>
              <div className="flex items-center gap-2 text-sm text-gray-900 dark:text-white"><Store className="h-4 w-4 text-emerald-600" />{merchantName}</div>
            </div>
            <div>
              <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</div>
              <div className="text-sm font-bold text-gray-900 dark:text-white">${price}</div>
            </div>
          </div>
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">Purchased at</div>
            <div className="text-sm text-gray-900 dark:text-white">{new Date(timestamp).toLocaleString()}</div>
          </div>
          {redemptionCode && (
            <div className="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/20">
              <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                <Ticket className="h-4 w-4" />
                Redemption code
              </div>
              <div className="font-mono text-lg font-black tracking-widest text-gray-900 dark:text-white">{redemptionCode}</div>
            </div>
          )}
        </div>

        <Button onClick={onClose} className="mt-6 w-full">Done</Button>
      </div>
    </div>
  )
}
