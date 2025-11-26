/**
 * Local Media Vault - React Hook
 *
 * Provides a convenient React interface for vault operations.
 */

import { useState, useEffect, useCallback } from 'react';
import {
  getVaultInfo,
  getVaultStatus,
  initializeVault,
  unlockVault,
  lockVault,
  isVaultUnlocked,
  addToVault,
  getFromVault,
  removeFromVault,
  listVaultItems,
  resetVault,
  exportVault,
  isVaultSupported,
  VaultStatus,
  VaultInfo,
  checkPassphraseStrength,
} from '@/lib/vault';
import { VaultMediaItem } from '@/lib/vault/storage';

export interface UseVaultReturn {
  // Status
  isSupported: boolean;
  status: VaultStatus;
  isUnlocked: boolean;
  info: VaultInfo | null;
  isLoading: boolean;
  error: string | null;

  // Items
  items: Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>[];

  // Actions
  initialize: (passphrase: string) => Promise<boolean>;
  unlock: (passphrase: string) => Promise<boolean>;
  lock: () => void;
  addFile: (
    file: File | Blob,
    options?: { originalName?: string; thumbnail?: Blob; remotePhotoId?: number }
  ) => Promise<string | null>;
  getFile: (id: string) => Promise<Blob | null>;
  removeFile: (id: string) => Promise<boolean>;
  exportVault: () => Promise<string | null>;
  reset: () => Promise<boolean>;
  refresh: () => Promise<void>;

  // Utilities
  checkStrength: typeof checkPassphraseStrength;
}

export function useVault(): UseVaultReturn {
  const [isSupported] = useState(() => isVaultSupported());
  const [status, setStatus] = useState<VaultStatus>('locked');
  const [info, setInfo] = useState<VaultInfo | null>(null);
  const [items, setItems] = useState<Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Refresh vault state
  const refresh = useCallback(async () => {
    if (!isSupported) {
      setStatus('unsupported');
      setIsLoading(false);
      return;
    }

    try {
      setIsLoading(true);
      const [newStatus, newInfo, newItems] = await Promise.all([
        getVaultStatus(),
        getVaultInfo(),
        isVaultUnlocked() ? listVaultItems() : Promise.resolve([]),
      ]);

      setStatus(newStatus);
      setInfo(newInfo);
      setItems(newItems);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load vault info');
    } finally {
      setIsLoading(false);
    }
  }, [isSupported]);

  // Initial load
  useEffect(() => {
    refresh();
  }, [refresh]);

  // Initialize vault
  const initialize = useCallback(async (passphrase: string): Promise<boolean> => {
    setIsLoading(true);
    setError(null);

    const result = await initializeVault(passphrase);
    if (!result.success) {
      setError(result.error || 'Failed to initialize vault');
      setIsLoading(false);
      return false;
    }

    await refresh();
    return true;
  }, [refresh]);

  // Unlock vault
  const unlock = useCallback(async (passphrase: string): Promise<boolean> => {
    setIsLoading(true);
    setError(null);

    const result = await unlockVault(passphrase);
    if (!result.success) {
      setError(result.error || 'Failed to unlock vault');
      setIsLoading(false);
      return false;
    }

    await refresh();
    return true;
  }, [refresh]);

  // Lock vault
  const lock = useCallback(() => {
    lockVault();
    setStatus('locked');
    setItems([]);
  }, []);

  // Add file
  const addFile = useCallback(
    async (
      file: File | Blob,
      options?: { originalName?: string; thumbnail?: Blob; remotePhotoId?: number }
    ): Promise<string | null> => {
      setError(null);

      const result = await addToVault(file, options);
      if (!result.success) {
        setError(result.error || 'Failed to add file');
        return null;
      }

      await refresh();
      return result.id || null;
    },
    [refresh]
  );

  // Get file
  const getFile = useCallback(async (id: string): Promise<Blob | null> => {
    setError(null);

    const result = await getFromVault(id);
    if (!result.success) {
      setError(result.error || 'Failed to get file');
      return null;
    }

    return result.blob || null;
  }, []);

  // Remove file
  const removeFile = useCallback(async (id: string): Promise<boolean> => {
    setError(null);

    const result = await removeFromVault(id);
    if (!result.success) {
      setError(result.error || 'Failed to remove file');
      return false;
    }

    await refresh();
    return true;
  }, [refresh]);

  // Reset vault
  const reset = useCallback(async (): Promise<boolean> => {
    setError(null);

    const result = await resetVault();
    if (!result.success) {
      setError(result.error || 'Failed to reset vault');
      return false;
    }

    await refresh();
    return true;
  }, [refresh]);

  // Export vault
  const doExportVault = useCallback(async (): Promise<string | null> => {
    setError(null);

    const result = await exportVault();
    if (!result.success) {
      setError(result.error || 'Failed to export vault');
      return null;
    }

    return result.data || null;
  }, []);

  return {
    isSupported,
    status,
    isUnlocked: status === 'unlocked',
    info,
    isLoading,
    error,
    items,
    initialize,
    unlock,
    lock,
    addFile,
    getFile,
    removeFile,
    exportVault: doExportVault,
    reset,
    refresh,
    checkStrength: checkPassphraseStrength,
  };
}
