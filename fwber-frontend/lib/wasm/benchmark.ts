'use client';

import * as Crypto from '@/lib/e2e/crypto';

export interface BenchmarkResult {
    wasmTime: number;
    jsTime: number;
    improvement: number;
    payloadSize: number;
}

export async function runEncryptionBenchmark(iterations = 100, payloadSize = 10000): Promise<BenchmarkResult> {
    const message = "A".repeat(payloadSize);
    const key = await window.crypto.subtle.generateKey(
        { name: 'AES-GCM', length: 256 },
        true,
        ['encrypt', 'decrypt']
    );

    // 1. Benchmark WebCrypto (JS)
    const jsStart = performance.now();
    for (let i = 0; i < iterations; i++) {
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        await window.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            key,
            new TextEncoder().encode(message)
        );
    }
    const jsTime = performance.now() - jsStart;

    // 2. Benchmark WASM (Rust)
    // Note: This requires the WASM module to be built and available
    // We'll use the crypto bridge's internal logic
    const wasmStart = performance.now();
    let wasmTime = 0;
    
    // @ts-ignore
    const wasm = await import('@/lib/wasm/fwber_wasm');
    if (wasm) {
        await wasm.default();
        const rawKey = await window.crypto.subtle.exportKey('raw', key);
        const keyHex = Array.from(new Uint8Array(rawKey)).map(b => b.toString(16).padStart(2, '0')).join('');
        
        for (let i = 0; i < iterations; i++) {
            wasm.encrypt_message(message, keyHex);
        }
        wasmTime = performance.now() - wasmStart;
    } else {
        wasmTime = -1; // Not available
    }

    return {
        wasmTime,
        jsTime,
        improvement: wasmTime > 0 ? ((jsTime - wasmTime) / jsTime) * 100 : 0,
        payloadSize
    };
}
