// Web Worker for E2E Media Decryption
// Offloads AES-GCM decryption from the main UI thread.

self.onmessage = async (e) => {
    const { id, type, encryptedBuffer, keyJwk, ivBase64 } = e.data;

    try {
        if (type === 'DECRYPT_MEDIA') {
            // 1. Re-import the raw CryptoKey
            const rawKeyArray = new Uint8Array(keyJwk.length);
            for (let i = 0; i < keyJwk.length; i++) {
                rawKeyArray[i] = keyJwk.charCodeAt(i);
            }
            const key = await crypto.subtle.importKey(
                'raw',
                rawKeyArray,
                { name: 'AES-GCM', length: 256 },
                false,
                ['decrypt']
            );

            // 2. Prepare the IV
            const ivStr = atob(ivBase64);
            const iv = new Uint8Array(ivStr.length);
            for (let i = 0; i < ivStr.length; i++) {
                iv[i] = ivStr.charCodeAt(i);
            }

            // 3. Decrypt the Buffer
            const decryptedBuffer = await crypto.subtle.decrypt(
                { name: 'AES-GCM', iv },
                key,
                encryptedBuffer
            );

            // 4. Return the decrypted blob
            self.postMessage({
                id,
                success: true,
                decryptedBuffer
            }, [decryptedBuffer]); // Transfer ownership
        }
    } catch (error) {
        self.postMessage({
            id,
            success: false,
            error: error.message || 'Decryption failed'
        });
    }
};
