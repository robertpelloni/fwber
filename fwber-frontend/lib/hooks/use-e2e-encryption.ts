import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import { securityApi } from '@/lib/api/security';
import { api } from '@/lib/api/client';
import * as Storage from '@/lib/e2e/storage';
import * as Crypto from '@/lib/e2e/crypto';

export function useE2EEncryption() {
  const { user, isAuthenticated, token } = useAuth();
  const [isReady, setIsReady] = useState(false);
  const [isRestorable, setIsRestorable] = useState(false);
  const [sharedKeys, setSharedKeys] = useState<Record<string, any>>({});

  // Initialize Keys
  useEffect(() => {
    if (!isAuthenticated || !user) return;

    const initKeys = async () => {
      try {
        let keyPair = await Storage.getKeyPair(user.id, 'ecdh');
        let metadata = await Storage.getKeyPairMetadata(user.id, 'ecdh');
        
        const KEY_MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000; // 30 days
        const needsRotation = metadata && (Date.now() - metadata.createdAt > KEY_MAX_AGE_MS);

        if (!keyPair || needsRotation) {
          // Check if we have a backup on the server before generating new ones
          try {
            const res = await api.get<any>(`/security/keys/restore?key_type=ecdh`, {
                headers: { Authorization: `Bearer ${token}` }
            });
            if (res.encrypted_private_key) {
                console.debug('E2E: Found remote backup. Marking as restorable.');
                setIsRestorable(true);
                return; // Wait for user to restore or rotate manually
            }
          } catch (e) {
            // No backup found, proceed to generation
          }

          console.debug(needsRotation ? 'E2E keys expired. Rotating...' : 'Generating initial E2E keys...');
          keyPair = await Crypto.generateKeyPair();
          await Storage.storeKeyPair(user.id, keyPair, 'ecdh');

          const publicKeyString = await Crypto.exportPublicKey(keyPair.publicKey);
          await securityApi.storePublicKey(publicKeyString);
          
          if (needsRotation) {
            setSharedKeys({}); // Clear cached shared keys on rotation
          }
        }
        setIsReady(true);
      } catch (error) {
        console.error('Failed to initialize E2E encryption:', error);
      }
    };

    initKeys();
  }, [user, isAuthenticated, token]);

  // Get Shared Key (Derive or Cache)
  const getSharedKey = useCallback(async (peerId: string | number) => {
    if (sharedKeys[String(peerId)]) return sharedKeys[String(peerId)];

    if (!user) throw new Error('User not authenticated');

    // Handle Local Peer (Number)
    const myKeys = await Storage.getKeyPair(user.id, 'ecdh');
    if (!myKeys) throw new Error('E2E keys not initialized');

    try {
      const { data } = await securityApi.getPublicKey(Number(peerId));
      const peerPublicKey = await Crypto.importPublicKey(data.public_key);

      const sharedKey = await Crypto.deriveSharedKey(myKeys.privateKey, peerPublicKey);

      setSharedKeys(prev => ({ ...prev, [String(peerId)]: { type: 'ecdh', key: sharedKey } }));
      return { type: 'ecdh', key: sharedKey };
    } catch (error) {
      console.error(`Failed to establish secure session with user ${peerId}`, error);
      throw error;
    }
  }, [user, sharedKeys]);

  const getSharedKeyRaw = useCallback(async (peerId: string | number) => {
    const keyData = await getSharedKey(peerId);
    if (keyData.type === 'ecdh') {
        const rawKey = await window.crypto.subtle.exportKey('raw', keyData.key);
        return Array.from(new Uint8Array(rawKey)).map(b => String.fromCharCode(b)).join('');
    }
    return null;
  }, [getSharedKey]);

  const encrypt = useCallback(async (peerId: string | number, text: string) => {
    const keyData = await getSharedKey(peerId);
    return Crypto.encryptMessage(text, keyData.key);
  }, [getSharedKey]);

  const decrypt = useCallback(async (peerId: string | number, encryptedText: string) => {
    const keyData = await getSharedKey(peerId);
    if (!keyData) return encryptedText; // Fallback
    return Crypto.decryptMessage(encryptedText, keyData.key);
  }, [getSharedKey]);

  const regenerateKeys = useCallback(async () => {
    if (!user) return;
    try {

      const keyPair = await Crypto.generateKeyPair();
      await Storage.storeKeyPair(user.id, keyPair, 'ecdh');

      const publicKeyString = await Crypto.exportPublicKey(keyPair.publicKey);
      await securityApi.storePublicKey(publicKeyString);

      // Clear cache
      setSharedKeys({});
      setIsRestorable(false);

    } catch (error) {
      console.error('Failed to regenerate keys:', error);
      throw error;
    }
  }, [user]);

  const backupKeys = useCallback(async (passphrase: string) => {
    if (!user || !token) throw new Error('Not authenticated');

    const ecdhKeys = await Storage.getKeyPair(user.id, 'ecdh');
    if (!ecdhKeys) throw new Error('Local keys not found for backup');

    const exportAndBackup = async (keys: CryptoKeyPair, keyType: 'ecdh') => {
        const jwk = await window.crypto.subtle.exportKey('jwk', keys.privateKey);
        const { ciphertext, salt, iv } = await Crypto.encryptPrivateKey(JSON.stringify(jwk), passphrase);

        await api.post('/security/keys/backup', {
            key_type: keyType,
            encrypted_private_key: ciphertext,
            salt,
            iv
        }, { headers: { Authorization: `Bearer ${token}` } });
    };

    await exportAndBackup(ecdhKeys, 'ecdh');
    setIsRestorable(false);
  }, [user, token]);

  const restoreKeys = useCallback(async (passphrase: string) => {
    if (!user || !token) throw new Error('Not authenticated');

    const fetchAndImport = async (keyType: 'ecdh') => {
        const res = await api.get<any>(`/security/keys/restore?key_type=${keyType}`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        const jwkString = await Crypto.decryptPrivateKey(
            res.encrypted_private_key, 
            res.salt, 
            res.iv, 
            passphrase
        );

        const jwk = JSON.parse(jwkString);
        
        const pubRes = await securityApi.getPublicKey(user.id);
        const publicKey = await Crypto.importPublicKey(pubRes.data.public_key);

        const algo = { name: 'ECDH', namedCurve: 'P-256' };
        const privUsages: KeyUsage[] = ['deriveKey', 'deriveBits'];

        const privateKey = await window.crypto.subtle.importKey('jwk', jwk, algo, true, privUsages);

        await Storage.storeKeyPair(user.id, { publicKey, privateKey }, keyType);
    };

    await fetchAndImport('ecdh');
    
    setIsReady(true);
    setIsRestorable(false);
    setSharedKeys({});
  }, [user, token]);

  return { isReady, isRestorable, encrypt, decrypt, regenerateKeys, backupKeys, restoreKeys, getSharedKeyRaw };
}
