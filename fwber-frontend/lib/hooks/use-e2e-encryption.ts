import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import { securityApi } from '@/lib/api/security';
import * as Storage from '@/lib/e2e/storage';
import * as Crypto from '@/lib/e2e/crypto';

export function useE2EEncryption() {
  const { user } = useAuth();
  const [isReady, setIsReady] = useState(false);
  const [sharedKeys, setSharedKeys] = useState<Record<number, CryptoKey>>({});

  // Initialize Keys
  useEffect(() => {
    if (!user) return;

    const initKeys = async () => {
      try {
        let keyPair = await Storage.getKeyPair(user.id);

        if (!keyPair) {
          console.log('Generating new E2E key pair...');
          keyPair = await Crypto.generateKeyPair();
          await Storage.storeKeyPair(user.id, keyPair);
          
          const publicKeyString = await Crypto.exportPublicKey(keyPair.publicKey);
          await securityApi.storePublicKey(publicKeyString);
          console.log('E2E Public Key uploaded.');
        }
        setIsReady(true);
      } catch (error) {
        console.error('Failed to initialize E2E encryption:', error);
      }
    };

    initKeys();
  }, [user]);

  // Get Shared Key (Derive or Cache)
  const getSharedKey = useCallback(async (peerId: number) => {
    if (sharedKeys[peerId]) return sharedKeys[peerId];

    if (!user) throw new Error('User not authenticated');

    // 1. Get my private key
    const myKeys = await Storage.getKeyPair(user.id);
    if (!myKeys) throw new Error('E2E keys not initialized');

    // 2. Get peer's public key from API
    try {
      const { data } = await securityApi.getPublicKey(peerId);
      const peerPublicKey = await Crypto.importPublicKey(data.public_key);

      // 3. Derive shared secret
      const sharedKey = await Crypto.deriveSharedKey(myKeys.privateKey, peerPublicKey);
      
      // Cache it
      setSharedKeys(prev => ({ ...prev, [peerId]: sharedKey }));
      return sharedKey;
    } catch (error) {
      console.error(`Failed to establish secure session with user ${peerId}`, error);
      throw error;
    }
  }, [user, sharedKeys]);

  const encrypt = useCallback(async (peerId: number, text: string) => {
    const key = await getSharedKey(peerId);
    return Crypto.encryptMessage(text, key);
  }, [getSharedKey]);

  const decrypt = useCallback(async (peerId: number, encryptedText: string) => {
    const key = await getSharedKey(peerId);
    return Crypto.decryptMessage(encryptedText, key);
  }, [getSharedKey]);

  return { isReady, encrypt, decrypt };
}
