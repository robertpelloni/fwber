import ipaddr from 'ipaddr.js';
import dns from 'dns';
import { promisify } from 'util';
import { URL } from 'url';

const resolve4 = promisify(dns.resolve4);
const resolve6 = promisify(dns.resolve6);

/**
 * Validates a URL to prevent SSRF by checking if its hostname resolves to a private IP.
 */
export async function validateFederationUrl(urlStr: string): Promise<boolean> {
    try {
        const url = new URL(urlStr);
        const hostname = url.hostname;

        // Block literal IPs that are private/loopback
        if (ipaddr.isValid(hostname)) {
            const addr = ipaddr.parse(hostname);
            if (isPrivateIp(addr)) {
                return false;
            }
        }

        // Resolve hostname to IPs and check them
        const ips: string[] = [];
        try {
            const v4 = await resolve4(hostname);
            ips.push(...v4);
        } catch {}
        try {
            const v6 = await resolve6(hostname);
            ips.push(...v6);
        } catch {}

        for (const ip of ips) {
            if (ipaddr.isValid(ip)) {
                if (isPrivateIp(ipaddr.parse(ip))) {
                    return false;
                }
            }
        }

        return true;
    } catch (e) {
        return false;
    }
}

function isPrivateIp(addr: ipaddr.IPv4 | ipaddr.IPv6): boolean {
    const range = addr.range();
    const privateRanges = [
        'loopback',
        'linkLocal',
        'private',
        'uniqueLocal',
        'reserved',
        'unspecified'
    ];

    // Explicitly block 172.16.0.0/12 which some libraries might categorize differently
    if (addr.kind() === 'ipv4') {
        const octets = addr.toByteArray();
        if (octets[0] === 172 && octets[1] >= 16 && octets[1] <= 31) {
            return true;
        }
    }

    return privateRanges.includes(range);
}
