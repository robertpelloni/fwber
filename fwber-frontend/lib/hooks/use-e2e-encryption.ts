import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import { securityApi } from '@/lib/api/security';
import { api } from '@/lib/api/client';
import * as Storage from '@/lib/e2e/storage';
import * as Crypto from '@/lib/e2e/crypto';

export function useE2EEncryption() {
  const { user } = useAuth();
  const [isReady, setIsReady] = useState(false);
  const [sharedKeys, setSharedKeys] = useState<Record<string, any>>({});

  // Initialize Keys
  useEffect(() => {
    if (!user) return;

    const initKeys = async () => {
      try {
        let keyPair = await Storage.getKeyPair(user.id);
        let metadata = await Storage.getKeyPairMetadata(user.id);
        
        const KEY_MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000; // 30 days
        const needsRotation = metadata && (Date.now() - metadata.createdAt > KEY_MAX_AGE_MS);

        if (!keyPair || needsRotation) {
          console.debug(needsRotation ? 'E2E keys expired. Rotating...' : 'Generating initial E2E keys...');
          keyPair = await Crypto.generateKeyPair();
          await Storage.storeKeyPair(user.id, keyPair);

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
  }, [user]);

  // Get Shared Key (Derive or Cache)
  const getSharedKey = useCallback(async (peerId: string | number) => {
    if (sharedKeys[String(peerId)]) return sharedKeys[String(peerId)];

    if (!user) throw new Error('User not authenticated');

    // Handle Federated Peer (URI)
    if (typeof peerId === 'string' && peerId.startsWith('http')) {
        try {
            // 1. Get remote actor detail
            const { actor } = await api.get<any>(`/federation/actors/detail?uri=${encodeURIComponent(peerId)}`);
            if (!actor.publicKey?.publicKeyPem) throw new Error('Remote actor has no public key');

            // 2. Import RSA Public Key
            const rsaPublicKey = await Crypto.importRsaPublicKey(actor.publicKey.publicKeyPem);
            
            // For federated, we return the RSA key directly
            setSharedKeys(prev => ({ ...prev, [peerId]: { type: 'rsa', key: rsaPublicKey } }));
            return { type: 'rsa', key: rsaPublicKey };
        } catch (error) {
            console.error(`Federated E2E failed for ${peerId}`, error);
            throw error;
        }
    }

    // Handle Local Peer (Number)
    const myKeys = await Storage.getKeyPair(user.id);
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

  const encrypt = useCallback(async (peerId: string | number, text: string) => {
    const { type, key } = await getSharedKey(peerId);
    if (type === 'rsa') {
        return Crypto.encryptWithRsa(text, key);
    }
    return Crypto.encryptMessage(text, key);
  }, [getSharedKey]);

  const decrypt = useCallback(async (peerId: string | number, encryptedText: string) => {
    const keyData = await getSharedKey(peerId);
    if (!keyData) return encryptedText; // Fallback
    
    if (keyData.type === 'rsa') {
        // RSA decryption requires our PRIVATE key which must also be RSA
        // Our current keys are ECDH. 
        // NOTE: In a full federated AP system, the user should have BOTH ECDH and RSA keys.
        // For this milestone, we've enabled OUTBOUND encryption.
        return encryptedText; 
    }
    return Crypto.decryptMessage(encryptedText, keyData.key);
  }, [getSharedKey]);

  const regenerateKeys = useCallback(async () => {
    if (!user) return;
    try {

      const keyPair = await Crypto.generateKeyPair();
      await Storage.storeKeyPair(user.id, keyPair);

      const publicKeyString = await Crypto.exportPublicKey(keyPair.publicKey);
      await securityApi.storePublicKey(publicKeyString);

      // Clear cache
      setSharedKeys({});

    } catch (error) {
      console.error('Failed to regenerate keys:', error);
      throw error;
    }
  }, [user]);

  return { isReady, encrypt, decrypt, regenerateKeys };
}
