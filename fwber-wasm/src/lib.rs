use aes_gcm::{
    aead::{Aead, KeyInit},
    Aes256Gcm, Nonce,
};
use rand::{rngs::OsRng, RngCore};
use wasm_bindgen::prelude::*;
use base64::{Engine as _, engine::general_purpose};

#[wasm_bindgen]
pub struct EncryptionResult {
    ciphertext: String,
    nonce: String,
}

#[wasm_bindgen]
impl EncryptionResult {
    #[wasm_bindgen(getter)]
    pub fn ciphertext(&self) -> String {
        self.ciphertext.clone()
    }

    #[wasm_bindgen(getter)]
    pub fn nonce(&self) -> String {
        self.nonce.clone()
    }
}

#[wasm_bindgen]
pub fn encrypt_message(message: &str, key_hex: &str) -> Result<EncryptionResult, JsValue> {
    let key_bytes = hex::decode(key_hex)
        .map_err(|_| JsValue::from_str("Invalid hex key"))?;
    
    if key_bytes.len() != 32 {
        return Err(JsValue::from_str("Key must be 32 bytes (64 hex chars)"));
    }

    let cipher = Aes256Gcm::new_from_slice(&key_bytes)
        .map_err(|e| JsValue::from_str(&e.to_string()))?;

    let mut nonce_bytes = [0u8; 12];
    OsRng.fill_bytes(&mut nonce_bytes);
    let nonce = Nonce::from_slice(&nonce_bytes);

    let ciphertext = cipher
        .encrypt(nonce, message.as_bytes())
        .map_err(|e| JsValue::from_str(&e.to_string()))?;

    Ok(EncryptionResult {
        ciphertext: general_purpose::STANDARD.encode(ciphertext),
        nonce: hex::encode(nonce_bytes),
    })
}

#[wasm_bindgen]
pub fn decrypt_message(ciphertext_b64: &str, nonce_hex: &str, key_hex: &str) -> Result<String, JsValue> {
    let key_bytes = hex::decode(key_hex)
        .map_err(|_| JsValue::from_str("Invalid hex key"))?;
    
    let nonce_bytes = hex::decode(nonce_hex)
        .map_err(|_| JsValue::from_str("Invalid hex nonce"))?;
        
    let ciphertext = general_purpose::STANDARD.decode(ciphertext_b64)
        .map_err(|_| JsValue::from_str("Invalid base64 ciphertext"))?;

    let cipher = Aes256Gcm::new_from_slice(&key_bytes)
        .map_err(|e| JsValue::from_str(&e.to_string()))?;

    let nonce = Nonce::from_slice(&nonce_bytes);

    let plaintext = cipher
        .decrypt(nonce, ciphertext.as_slice())
        .map_err(|e| JsValue::from_str(&e.to_string()))?;

    String::from_utf8(plaintext)
        .map_err(|_| JsValue::from_str("Invalid UTF-8 sequence"))
}
