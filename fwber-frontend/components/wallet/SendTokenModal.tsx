'use client';

import React, { useState, useEffect } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { Loader2, Send } from 'lucide-react';
import { apiClient } from '@/lib/api/client';

interface SendTokenModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function SendTokenModal({ isOpen, onClose, onSuccess }: SendTokenModalProps) {
  const [friends, setFriends] = useState<any[]>([]);
  const [recipientType, setRecipientType] = useState<'friend' | 'email'>('friend');
  const [selectedRecipient, setSelectedRecipient] = useState('');
  const [email, setEmail] = useState('');
  const [amount, setAmount] = useState('');
  const [message, setMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen && recipientType === 'friend') {
      apiClient.get('/friends').then(res => {
          const data = res.data as { data?: any[] } | any[];
          setFriends(Array.isArray(data) ? data : (data.data || []));
      });
    }
  }, [isOpen, recipientType]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if ((recipientType === 'friend' && !selectedRecipient) || (recipientType === 'email' && !email) || !amount) return;

    setSubmitting(true);
    try {
      await apiClient.post('/wallet/transfer', {
        ...(recipientType === 'friend' ? { recipient_id: selectedRecipient } : { recipient_email: email }),
        amount: parseFloat(amount),
        message
      });
      alert('Sent successfully!');
      onSuccess();
      onClose();
      setAmount('');
      setMessage('');
      setSelectedRecipient('');
      setEmail('');
    } catch (error: any) {
      alert(error.response?.data?.error || 'Failed to send tokens');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={onClose}>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 bg-black/50 z-50 backdrop-blur-sm" />
        <Dialog.Content className="fixed left-[50%] top-[50%] max-h-[85vh] w-[90vw] max-w-[450px] translate-x-[-50%] translate-y-[-50%] rounded-xl bg-white p-6 shadow-2xl focus:outline-none z-50 dark:bg-gray-900 dark:text-white border border-gray-200 dark:border-gray-700">
          <Dialog.Title className="text-xl font-bold mb-4 flex items-center gap-2">
            <Send className="w-5 h-5 text-indigo-600" />
            Send Tokens
          </Dialog.Title>

          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Recipient Toggle */}
            <div className="flex gap-2 mb-4 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <button
                    type="button"
                    onClick={() => setRecipientType('friend')}
                    className={`flex-1 py-1.5 text-xs font-medium rounded-md transition-all ${
                        recipientType === 'friend' ? 'bg-white shadow text-indigo-600' : 'text-gray-500'
                    }`}
                >
                    Select Friend
                </button>
                <button
                    type="button"
                    onClick={() => setRecipientType('email')}
                    className={`flex-1 py-1.5 text-xs font-medium rounded-md transition-all ${
                        recipientType === 'email' ? 'bg-white shadow text-indigo-600' : 'text-gray-500'
                    }`}
                >
                    Enter Email
                </button>
            </div>

            {recipientType === 'friend' ? (
                <div>
                <label className="block text-sm font-medium mb-1">Recipient</label>
                <select
                    value={selectedRecipient}
                    onChange={e => setSelectedRecipient(e.target.value)}
                    className="w-full p-2 border rounded-lg dark:bg-gray-800 dark:border-gray-700"
                    required={recipientType === 'friend'}
                >
                    <option value="">Select a friend...</option>
                    {friends.map(f => (
                    <option key={f.id} value={f.id}>{f.name}</option>
                    ))}
                </select>
                {friends.length === 0 && <p className="text-xs text-gray-500 mt-1">No friends found.</p>}
                </div>
            ) : (
                <div>
                    <label className="block text-sm font-medium mb-1">Recipient Email</label>
                    <input
                        type="email"
                        value={email}
                        onChange={e => setEmail(e.target.value)}
                        className="w-full p-2 border rounded-lg dark:bg-gray-800 dark:border-gray-700"
                        placeholder="friend@example.com"
                        required={recipientType === 'email'}
                    />
                </div>
            )}

            <div>
              <label className="block text-sm font-medium mb-1">Amount (FWB)</label>
              <input
                type="number"
                min="1"
                step="0.1"
                value={amount}
                onChange={e => setAmount(e.target.value)}
                className="w-full p-2 border rounded-lg dark:bg-gray-800 dark:border-gray-700"
                placeholder="0.00"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Message (Optional)</label>
              <input
                type="text"
                value={message}
                onChange={e => setMessage(e.target.value)}
                className="w-full p-2 border rounded-lg dark:bg-gray-800 dark:border-gray-700"
                placeholder="What's this for?"
                maxLength={255}
              />
            </div>

            <div className="flex gap-2 pt-2">
              <button
                type="button"
                onClick={onClose}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={submitting || (recipientType === 'friend' ? !selectedRecipient : !email)}
                className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex justify-center items-center gap-2"
              >
                {submitting && <Loader2 className="w-4 h-4 animate-spin" />}
                Send Now
              </button>
            </div>
          </form>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
