/**
 * Local Media Vault - Main Vault Service
 *
 * High-level API for managing the encrypted media vault.
 * Combines crypto utilities with IndexedDB storage.
 */

import {
  encryptBlob,
  decryptBlob,
  generateSalt,
  arrayBufferToBase64,
  base64ToArrayBuffer,
  createPassphraseVerifier,
  verifyPassphrase,
  checkPassphraseStrength,
} from './crypto';

import {
  storeMedia,
  getMedia,
  deleteMedia,
  listMedia,
  getMediaByRemoteId,
  countMedia,
  calculateStorageUsed,
  setMetadata,
  getMetadata,
  deleteMetadata,
  clearAllVaultData,
  isIndexedDBSupported,
  VaultMediaItem,
} from './storage';

const SALT_KEY = 'vault_salt';
const VERIFIER_KEY = 'vault_verifier';
const ENABLED_KEY = 'vault_enabled';

export type VaultStatus = 'uninitialized' | 'locked' | 'unlocked' | 'unsupported';

export interface VaultInfo {
  status: VaultStatus;
  mediaCount: number;
  storageUsedBytes: number;
  storageUsedFormatted: string;
}

let currentPassphrase: string | null = null;
let cachedSalt: Uint8Array | null = null;

/**
 * Format bytes to human-readable string
 */
function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
}

/**
 * Generate a unique ID for vault items
 */
function generateId(): string {
  return `vault_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

/**
 * Check if the vault is supported on this browser
 */
export function isVaultSupported(): boolean {
  return isIndexedDBSupported() && typeof crypto !== 'undefined' && typeof crypto.subtle !== 'undefined';
}

/**
 * Get the current vault status
 */
export async function getVaultStatus(): Promise<VaultStatus> {
  if (!isVaultSupported()) return 'unsupported';

  const salt = await getMetadata(SALT_KEY);
  const verifier = await getMetadata(VERIFIER_KEY);

  if (!salt || !verifier) return 'uninitialized';
  if (currentPassphrase && cachedSalt) return 'unlocked';
  return 'locked';
}

/**
 * Get comprehensive vault info
 */
export async function getVaultInfo(): Promise<VaultInfo> {
  const status = await getVaultStatus();
  const mediaCount = status !== 'unsupported' ? await countMedia() : 0;
  const storageUsedBytes = status !== 'unsupported' ? await calculateStorageUsed() : 0;

  return {
    status,
    mediaCount,
    storageUsedBytes,
    storageUsedFormatted: formatBytes(storageUsedBytes),
  };
}

/**
 * Initialize a new vault with a passphrase
 */
export async function initializeVault(passphrase: string): Promise<{ success: boolean; error?: string }> {
  if (!isVaultSupported()) {
    return { success: false, error: 'Vault is not supported on this browser' };
  }

  const strength = checkPassphraseStrength(passphrase);
  if (strength.score < 40) {
    return { success: false, error: 'Passphrase is too weak. ' + strength.feedback.join(' ') };
  }

  const existingSalt = await getMetadata(SALT_KEY);
  if (existingSalt) {
    return { success: false, error: 'Vault is already initialized. Use unlockVault() instead.' };
  }

  // Generate and store salt
  const salt = generateSalt();
  await setMetadata(SALT_KEY, arrayBufferToBase64(salt));

  // Create and store verifier
  const verifier = await createPassphraseVerifier(passphrase, salt);
  await setMetadata(VERIFIER_KEY, verifier);

  // Mark vault as enabled
  await setMetadata(ENABLED_KEY, 'true');

  // Keep passphrase in memory
  currentPassphrase = passphrase;
  cachedSalt = salt;

  return { success: true };
}

/**
 * Unlock the vault with the user's passphrase
 */
export async function unlockVault(passphrase: string): Promise<{ success: boolean; error?: string }> {
  if (!isVaultSupported()) {
    return { success: false, error: 'Vault is not supported on this browser' };
  }

  const saltBase64 = await getMetadata(SALT_KEY);
  const verifier = await getMetadata(VERIFIER_KEY);

  if (!saltBase64 || !verifier) {
    return { success: false, error: 'Vault is not initialized. Use initializeVault() first.' };
  }

  const salt = base64ToArrayBuffer(saltBase64);
  const isValid = await verifyPassphrase(passphrase, salt, verifier);

  if (!isValid) {
    return { success: false, error: 'Incorrect passphrase' };
  }

  currentPassphrase = passphrase;
  cachedSalt = salt;

  return { success: true };
}

/**
 * Lock the vault (clear passphrase from memory)
 */
export function lockVault(): void {
  currentPassphrase = null;
  cachedSalt = null;
}

/**
 * Check if vault is unlocked
 */
export function isVaultUnlocked(): boolean {
  return currentPassphrase !== null && cachedSalt !== null;
}

/**
 * Add a file to the vault
 */
export async function addToVault(
  file: File | Blob,
  options: {
    originalName?: string;
    thumbnail?: Blob;
    remotePhotoId?: number;
  } = {}
): Promise<{ success: boolean; id?: string; error?: string }> {
  if (!isVaultUnlocked()) {
    return { success: false, error: 'Vault is locked. Unlock it first.' };
  }

  try {
    const id = generateId();
    const mimeType = file.type || 'application/octet-stream';
    const encryptedBlob = await encryptBlob(file, currentPassphrase!, cachedSalt!);

    let thumbnailBlob: Blob | undefined;
    if (options.thumbnail) {
      thumbnailBlob = await encryptBlob(options.thumbnail, currentPassphrase!, cachedSalt!);
    }

    const item: VaultMediaItem = {
      id,
      originalName: options.originalName || (file instanceof File ? file.name : 'unnamed'),
      mimeType,
      encryptedBlob,
      thumbnailBlob,
      createdAt: Date.now(),
      updatedAt: Date.now(),
      size: file.size,
      remotePhotoId: options.remotePhotoId,
    };

    await storeMedia(item);
    return { success: true, id };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Unknown error' };
  }
}

/**
 * Retrieve and decrypt a file from the vault
 */
export async function getFromVault(
  id: string
): Promise<{ success: boolean; blob?: Blob; metadata?: Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>; error?: string }> {
  if (!isVaultUnlocked()) {
    return { success: false, error: 'Vault is locked. Unlock it first.' };
  }

  try {
    const item = await getMedia(id);
    if (!item) {
      return { success: false, error: 'Item not found' };
    }

    const decryptedBlob = await decryptBlob(
      item.encryptedBlob,
      currentPassphrase!,
      cachedSalt!,
      item.mimeType
    );

    const { encryptedBlob, thumbnailBlob, ...metadata } = item;

    return { success: true, blob: decryptedBlob, metadata };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Decryption failed' };
  }
}

/**
 * Get decrypted thumbnail from vault
 */
export async function getThumbnailFromVault(
  id: string
): Promise<{ success: boolean; blob?: Blob; error?: string }> {
  if (!isVaultUnlocked()) {
    return { success: false, error: 'Vault is locked. Unlock it first.' };
  }

  try {
    const item = await getMedia(id);
    if (!item) {
      return { success: false, error: 'Item not found' };
    }

    if (!item.thumbnailBlob) {
      return { success: false, error: 'No thumbnail available' };
    }

    const decryptedBlob = await decryptBlob(
      item.thumbnailBlob,
      currentPassphrase!,
      cachedSalt!,
      'image/jpeg' // Thumbnails are always JPEG
    );

    return { success: true, blob: decryptedBlob };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Decryption failed' };
  }
}

/**
 * Remove an item from the vault
 */
export async function removeFromVault(id: string): Promise<{ success: boolean; error?: string }> {
  try {
    await deleteMedia(id);
    return { success: true };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Delete failed' };
  }
}

/**
 * List all vault items (metadata only)
 */
export async function listVaultItems(): Promise<Omit<VaultMediaItem, 'encryptedBlob' | 'thumbnailBlob'>[]> {
  return listMedia();
}

/**
 * Find vault item by remote photo ID
 */
export async function findByRemotePhotoId(
  remotePhotoId: number
): Promise<{ success: boolean; item?: VaultMediaItem; error?: string }> {
  try {
    const item = await getMediaByRemoteId(remotePhotoId);
    if (!item) {
      return { success: false, error: 'Item not found' };
    }
    return { success: true, item };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Lookup failed' };
  }
}

/**
 * Change the vault passphrase
 * Requires the vault to be unlocked with the old passphrase first
 */
export async function changePassphrase(
  newPassphrase: string
): Promise<{ success: boolean; error?: string }> {
  if (!isVaultUnlocked()) {
    return { success: false, error: 'Vault is locked. Unlock it first.' };
  }

  const strength = checkPassphraseStrength(newPassphrase);
  if (strength.score < 40) {
    return { success: false, error: 'New passphrase is too weak. ' + strength.feedback.join(' ') };
  }

  try {
    // Get all media items
    const items = await listMedia();

    // Re-encrypt each item with new passphrase
    const newSalt = generateSalt();

    for (const meta of items) {
      const fullItem = await getMedia(meta.id);
      if (!fullItem) continue;

      // Decrypt with old passphrase
      const decryptedBlob = await decryptBlob(
        fullItem.encryptedBlob,
        currentPassphrase!,
        cachedSalt!,
        fullItem.mimeType
      );

      // Re-encrypt with new passphrase
      const reEncryptedBlob = await encryptBlob(decryptedBlob, newPassphrase, newSalt);

      let reEncryptedThumbnail: Blob | undefined;
      if (fullItem.thumbnailBlob) {
        const decryptedThumb = await decryptBlob(
          fullItem.thumbnailBlob,
          currentPassphrase!,
          cachedSalt!,
          'image/jpeg'
        );
        reEncryptedThumbnail = await encryptBlob(decryptedThumb, newPassphrase, newSalt);
      }

      // Update the stored item
      await storeMedia({
        ...fullItem,
        encryptedBlob: reEncryptedBlob,
        thumbnailBlob: reEncryptedThumbnail,
        updatedAt: Date.now(),
      });
    }

    // Update salt and verifier
    await setMetadata(SALT_KEY, arrayBufferToBase64(newSalt));
    const newVerifier = await createPassphraseVerifier(newPassphrase, newSalt);
    await setMetadata(VERIFIER_KEY, newVerifier);

    // Update cached values
    currentPassphrase = newPassphrase;
    cachedSalt = newSalt;

    return { success: true };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Passphrase change failed' };
  }
}

/**
 * Export vault data as an encrypted JSON bundle
 * For backup/migration purposes
 */
export async function exportVault(): Promise<{ success: boolean; data?: string; error?: string }> {
  if (!isVaultUnlocked()) {
    return { success: false, error: 'Vault is locked. Unlock it first.' };
  }

  try {
    const items = await listMedia();
    const exportData: Array<{
      id: string;
      originalName: string;
      mimeType: string;
      size: number;
      createdAt: number;
      encryptedData: string;
      thumbnailData?: string;
    }> = [];

    for (const meta of items) {
      const fullItem = await getMedia(meta.id);
      if (!fullItem) continue;

      exportData.push({
        id: fullItem.id,
        originalName: fullItem.originalName,
        mimeType: fullItem.mimeType,
        size: fullItem.size,
        createdAt: fullItem.createdAt,
        encryptedData: arrayBufferToBase64(await fullItem.encryptedBlob.arrayBuffer()),
        thumbnailData: fullItem.thumbnailBlob
          ? arrayBufferToBase64(await fullItem.thumbnailBlob.arrayBuffer())
          : undefined,
      });
    }

    const bundle = {
      version: 1,
      exportedAt: Date.now(),
      salt: await getMetadata(SALT_KEY),
      verifier: await getMetadata(VERIFIER_KEY),
      items: exportData,
    };

    return { success: true, data: JSON.stringify(bundle) };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Export failed' };
  }
}

/**
 * Reset the vault (delete all data)
 * ⚠️ DESTRUCTIVE - all data will be lost
 */
export async function resetVault(): Promise<{ success: boolean; error?: string }> {
  try {
    await clearAllVaultData();
    lockVault();
    return { success: true };
  } catch (error) {
    return { success: false, error: error instanceof Error ? error.message : 'Reset failed' };
  }
}

// Re-export utilities
export { checkPassphraseStrength } from './crypto';
